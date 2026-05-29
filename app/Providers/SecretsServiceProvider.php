<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SecretsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only run vault integration in production
        if (app()->environment('production')) {
            $this->loadSecretsFromVault();
        }
    }

    /**
     * Load secrets from an external vault provider (e.g., Doppler, AWS Secrets Manager, HashiCorp Vault)
     */
    protected function loadSecretsFromVault(): void
    {
        // To avoid making external API calls on every request, we cache the fetched secrets
        $secrets = Cache::remember('vault_secrets', now()->addHours(6), function () {
            try {
                // Example request using Doppler config service or AWS Secrets Manager.
                // Authenticaton token is retrieved from service config file
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.vault.token'),
                ])->timeout(config('services.vault.timeout'))->get(config('services.vault.url'));

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Throwable $e) {
                // Log failure to connect to secret manager
                \Log::error('Failed to load secrets from Vault: ' . $e->getMessage());
            }

            return [];
        });

        foreach ($secrets as $key => $value) {
            // Inject into PHP environment variables
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;

            // Map vault overrides directly to configured DB and third-party values
            if ($key === 'DB_PASSWORD') {
                config(['database.connections.mysql.password' => $value]);
            }
            if ($key === 'STRIPE_SECRET') {
                config(['services.stripe.secret' => $value]);
            }
        }
    }
}
