<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class SecretsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $driver = config('secrets.driver', 'env');

        // env driver — .env already loaded by Laravel, nothing to do
        if ($driver === 'env') {
            return;
        }

        try {
            $secrets = $this->resolveSecrets($driver);
            $this->applySecrets($secrets);
        } catch (\Throwable $e) {
            // fail hard on production — never boot with missing secrets
            if (app()->environment('production', 'staging')) {
                throw new RuntimeException(
                    "[SecretsServiceProvider] Failed to load secrets via driver [{$driver}]: " . $e->getMessage(),
                    previous: $e
                );
            }

            // on local — log warning and continue with .env values
            Log::warning("[SecretsServiceProvider] Secret load failed, falling back to .env: " . $e->getMessage());
        }
    }

    // ── Router ────────────────────────────────────────────────────────────────

    private function resolveSecrets(string $driver): array
    {
        $cacheEnabled = config('secrets.cache.enabled', true);
        $cacheTtl     = config('secrets.cache.ttl', 3600);
        $cacheKey     = config('secrets.cache.key', 'secrets_provider_cache') . "_{$driver}";

        if ($cacheEnabled && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $secrets = match ($driver) {
            'aws'     => $this->loadFromAws(),
            'vault'   => $this->loadFromVault(),
            'doppler' => $this->loadFromDoppler(),
            default   => throw new RuntimeException("Unsupported secrets driver: [{$driver}]"),
        };

        if ($cacheEnabled && ! empty($secrets)) {
            Cache::put($cacheKey, $secrets, $cacheTtl);
        }

        return $secrets;
    }

    // ── Apply secrets to Laravel config ──────────────────────────────────────

    private function applySecrets(array $secrets): void
    {
        foreach ($secrets as $key => $value) {
            // support dot-notation keys: "db.password" → config('db.password')
            Config::set($key, $value);

            // also push into $_ENV and $_SERVER so getenv() works
            $envKey = strtoupper(str_replace('.', '_', $key));
            $_ENV[$envKey]    = $value;
            $_SERVER[$envKey] = $value;
            putenv("{$envKey}={$value}");
        }
    }

    // ── Driver: AWS Secrets Manager ───────────────────────────────────────────

    private function loadFromAws(): array
    {
        if (! class_exists(\Aws\SecretsManager\SecretsManagerClient::class)) {
            throw new RuntimeException(
                'AWS driver requires: composer require aws/aws-sdk-php'
            );
        }

        $config = config('secrets.aws');

        if (empty($config['secret_name'])) {
            throw new RuntimeException('secrets.aws.secret_name is not set.');
        }

        $clientConfig = [
            'version' => 'latest',
            'region'  => $config['region'],
        ];

        // explicit credentials (non-IAM environments)
        if (! empty($config['key']) && ! empty($config['secret'])) {
            $clientConfig['credentials'] = [
                'key'    => $config['key'],
                'secret' => $config['secret'],
            ];
        }
        // else: SDK auto-resolves via IAM role / instance profile (recommended for EC2/ECS)

        $client = new \Aws\SecretsManager\SecretsManagerClient($clientConfig);

        try {
            $result = $client->getSecretValue([
                'SecretId' => $config['secret_name'],
            ]);
        } catch (\Aws\Exception\AwsException $e) {
            throw new RuntimeException(
                'AWS Secrets Manager error: ' . $e->getAwsErrorMessage(),
                previous: $e
            );
        }

        $raw = $result['SecretString'] ?? base64_decode($result['SecretBinary'] ?? '');

        if (empty($raw)) {
            throw new RuntimeException('AWS returned an empty secret value.');
        }

        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }

    // ── Driver: HashiCorp Vault ───────────────────────────────────────────────

    private function loadFromVault(): array
    {
        $config = config('secrets.vault');

        if (empty($config['addr']) || empty($config['path'])) {
            throw new RuntimeException(
                'secrets.vault.addr and secrets.vault.path must be set.'
            );
        }

        $token = $this->resolveVaultToken($config);

        $url      = rtrim($config['addr'], '/') . '/v1/' . ltrim($config['path'], '/');
        $response = Http::withToken($token)
            ->timeout(5)
            ->retry(3, 500)
            ->get($url);

        if ($response->unauthorized()) {
            throw new RuntimeException('Vault: invalid or expired token.');
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'Vault request failed [' . $response->status() . ']: ' . $response->body()
            );
        }

        // KV v2 stores data under data.data, KV v1 under data
        return $response->json('data.data')
            ?? $response->json('data')
            ?? throw new RuntimeException('Vault: unexpected response structure.');
    }

    private function resolveVaultToken(array $config): string
    {
        // AppRole auth (recommended for production — no long-lived token)
        if ($config['approle']['enabled'] ?? false) {
            $roleId   = $config['approle']['role_id']   ?? '';
            $secretId = $config['approle']['secret_id'] ?? '';

            if (empty($roleId) || empty($secretId)) {
                throw new RuntimeException(
                    'Vault AppRole requires VAULT_ROLE_ID and VAULT_SECRET_ID.'
                );
            }

            $addr     = rtrim($config['addr'], '/');
            $response = Http::timeout(5)
                ->post("{$addr}/v1/auth/approle/login", [
                    'role_id'   => $roleId,
                    'secret_id' => $secretId,
                ]);

            if ($response->failed()) {
                throw new RuntimeException('Vault AppRole login failed: ' . $response->body());
            }

            return $response->json('auth.client_token')
                ?? throw new RuntimeException('Vault AppRole: no client_token in response.');
        }

        // Static token fallback
        if (empty($config['token'])) {
            throw new RuntimeException(
                'Vault requires VAULT_TOKEN or AppRole credentials.'
            );
        }

        return $config['token'];
    }

    // ── Driver: Doppler ───────────────────────────────────────────────────────

    private function loadFromDoppler(): array
    {
        $token   = config('secrets.doppler.token');
        $project = config('secrets.doppler.project');
        $cfg     = config('secrets.doppler.config');

        if (empty($token)) {
            throw new RuntimeException('secrets.doppler.token is not set.');
        }

        $response = Http::withToken($token)
            ->timeout(5)
            ->retry(3, 500)
            ->get('https://api.doppler.com/v3/configs/config/secrets/download', [
                'project' => $project,
                'config'  => $cfg,
                'format'  => 'json',
            ]);

        if ($response->unauthorized()) {
            throw new RuntimeException('Doppler: invalid service token.');
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'Doppler request failed [' . $response->status() . ']: ' . $response->body()
            );
        }

        // Doppler returns { "KEY": { "raw": "value" } } or flat { "KEY": "value" }
        $body = $response->json();

        return collect($body)
            ->mapWithKeys(function ($value, $key) {
                $resolved = is_array($value) ? ($value['raw'] ?? $value['computed'] ?? '') : $value;
                return [strtolower($key) => $resolved];
            })
            ->all();
    }

    public function boot(): void {}
}