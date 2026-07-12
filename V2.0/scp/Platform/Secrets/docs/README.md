# Platform Secrets

**Package:** `platform/secrets`  
**Version:** 0.1.0  
**Layer:** Platform Kernel (Layer 1)  
**Traceability:** ADR-007, ADR-023, Platform OS Ch. 13

## Purpose

Secrets and credential vault for tenant-scoped API keys, PSP credentials, and integration tokens.

## Phase 1 Scope (ADR-007)

- `SecretVaultInterface` — `get(key): ?string`, `set(key, value)`
- `FileSecretVault` — file-based driver reading from `config/secrets.php` paths (never commit secrets)
- Singleton binding via `SecretsServiceProvider`
- Health endpoint: `GET /api/v1/platform/secrets/health`

### Configuration

| Env var | Config key | Default |
|---------|------------|---------|
| `SECRETS_DRIVER` | `secrets.driver` | `file` |
| `SECRETS_FILE_PATH` | `secrets.paths.default` | `storage/secrets` |

## References

- [Platform OS Ch. 13 §3](../../../docs/03-architecture/13-platform-os-architecture.md)
- [ADR-007 Secrets Management](../../../docs/00-meta/adr/007-secrets-management.md)
