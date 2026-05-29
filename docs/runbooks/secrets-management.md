# Runbook: Secrets Management and Environment Encryption

This runbook outlines the application's secrets-management strategy across all environments (Development, Staging, and Production), how secrets are injected at boot, and how to use Laravel's native environment encryption for deployments.

---

## 1. Secrets Management Strategy

We divide secret management into three distinct tiers:

### A. Development (Local)
- **Tool**: Plaintext `.env` file.
- **Rule**: Stored locally on developer machines. Never committed to git.
- **Access**: Each developer manages their own values, using `.env.example` as a template.

### B. Staging
- **Tool**: Plaintext `.env` file with restricted host access.
- **Rule**: Managed by CI/CD configuration variables (e.g., GitHub Actions Secrets, GitLab CI/CD Variables) and generated dynamically on the staging server during build/deploy.
- **Access**: Restricted to team leads and DevOps engineers.

### C. Production (Vault Integration)
- **Tool**: External secret manager (e.g., HashiCorp Vault, AWS Secrets Manager, Doppler).
- **Rule**: No long-lived sensitive database credentials or third-party keys are stored in a physical `.env` file. Instead, only non-sensitive configuration keys are stored in a decrypted `.env` file or injected environment.
- **Access**: Restricted to automated IAM roles or Vault access tokens.

---

## 2. Boot-Time Secret Injection (Production)

For production, we use a custom Laravel service provider to fetch secrets from the vault during the framework boot cycle and register them dynamically into PHP's environment and Laravel's configurations.

### Registration:
The [SecretsServiceProvider](file:///d:/laravel/laravel12-app/app/Providers/SecretsServiceProvider.php) is registered in [bootstrap/providers.php](file:///d:/laravel/laravel12-app/bootstrap/providers.php).

### Workflow:
1. When `app()->environment('production')` is true, the provider runs the `loadSecretsFromVault()` method.
2. It fetches secret JSON definitions from the vault API (using the injected `VAULT_TOKEN` environment variable from `config(services.vault.token)`).
3. To protect boot performance, it caches the secrets array for **6 hours**.
4. Loop through secrets and call `putenv("KEY=VAL")`, `$_ENV['KEY'] = 'VAL'`, and `config(['key' => 'VAL'])` to inject the secrets directly into PHP.

---

## 3. Environment File Encryption

For environments that still require an `.env` file on disk (like staging or production fallback configs), we keep environment files encrypted inside the repository.

### How to Encrypt (Before committing):
To encrypt the production environment file, run:
```bash
php artisan env:encrypt --env=production --key=YOUR_32_CHARACTER_ENCRYPTION_KEY --force
```
This produces `.env.production.encrypted`, which is safe to commit to version control.

### How to Decrypt (At deploy time):
During your CI/CD deployment pipeline, run:
```bash
php artisan env:decrypt --env=production --key=YOUR_32_CHARACTER_ENCRYPTION_KEY
```
This reads `.env.production.encrypted` and outputs the decrypted `.env.production` file ready for use.

> [!WARNING]
> Ensure the encryption key is kept secure inside your CI/CD secrets (e.g. `LARAVEL_ENV_ENCRYPTION_KEY`). Never commit the encryption key to the repository.
