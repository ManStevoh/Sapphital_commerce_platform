# Chapter 07: Environments & Configuration

**Document ID:** SCP-INF-001-07  
**Version:** 1.0.0  
**Status:** 📝 Draft  
**Traceability:** ADR-007, ADR-011, NFR-044, NFR-045  

---

## 1. Purpose

Define **environment topology**, configuration management, and secrets handling so developers, staging, and Lagos production remain consistent — without credential leakage or config drift.

## 2. Scope

- Environment definitions (local, staging, production)
- Configuration hierarchy and `.env` standards
- Secrets management lifecycle
- Region-specific overrides

## 3. Out of Scope

- Developer laptop setup scripts (Volume 20 playbooks)
- Merchant-facing environment settings (tenant admin UI)

## 4. Environment Matrix

| Environment | Region | Purpose | Data |
|-------------|--------|---------|------|
| **local** | Developer machine | Feature development | Synthetic / seeded |
| **ci** | Ephemeral | Automated tests | Fixtures, destroyed after job |
| **staging** | `af-ng-lagos` | Pre-prod validation | Anonymized prod subset or full synthetic |
| **production** | `af-ng-lagos` | Live merchants | Real |
| **production-ke** | `af-ke-nairobi` | Kenya GA (Phase 2) | Real KE tenants |

```text
Rule: Staging must mirror production topology (same Docker services, scaled down).
Exception documented if unavoidable — requires Lead Architect approval.
```

## 5. Service Parity

| Service | local | staging | production |
|---------|-------|---------|------------|
| FrankenPHP Octane | ✅ | ✅ | ✅ |
| PostgreSQL 16 | ✅ | ✅ | ✅ |
| PgBouncer | Optional | ✅ | ✅ |
| Redis | ✅ | ✅ | ✅ |
| Meilisearch | ✅ | ✅ | ✅ |
| Horizon | ✅ | ✅ | ✅ |
| Cloudflare | Tunnel or bypass | Full proxy | Full proxy |
| R2 | Dev bucket | Staging bucket | Prod bucket |

## 6. Configuration Hierarchy

```text
1. Default config (config/*.php) — no secrets
2. Environment .env file — secrets + env-specific URLs
3. Runtime overrides (feature flags) — Redis/DB
4. Tenant settings — merchant-specific (not infra)
```

### 6.1 Required Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_ENV` | Environment name | `production` |
| `APP_KEY` | Laravel encryption key | `base64:...` |
| `APP_URL` | Canonical URL | `https://app.sapphital.com` |
| `DB_URL` | PgBouncer connection | `pgsql://...@pgbouncer:6432/scp` |
| `REDIS_URL` | Redis connection | `redis://redis:6379` |
| `MEILISEARCH_HOST` | Search URL | `http://meilisearch:7700` |
| `MEILISEARCH_KEY` | Master key | secret |
| `AWS_ACCESS_KEY_ID` | R2 access key | — |
| `AWS_SECRET_ACCESS_KEY` | R2 secret | — |
| `AWS_ENDPOINT` | R2 endpoint | `https://...r2.cloudflarestorage.com` |
| `AWS_BUCKET` | Media bucket | `scp-prod-media` |
| `SCP_PRIMARY_REGION` | Logical region | `af-ng-lagos` |
| `SENTRY_DSN` | Error tracking | — |
| `CLOUDFLARE_API_TOKEN` | Purge/DNS automation | scoped token |

### 6.2 Forbidden in Repository

- Production credentials
- `APP_KEY` for non-local environments
- Private keys, webhook signing secrets
- Unencrypted `.env.production` files

Enforced by gitleaks in CI (NFR-042).

## 7. Secrets Management (ADR-007)

### Phase 1

| Approach | Detail |
|----------|--------|
| Storage | Encrypted `.env` on server (`ansible-vault` or SOPS) |
| Delivery | CI injects at deploy via SSH or secret store |
| Rotation | Quarterly or on personnel change |
| Access | Lead Architect + on-call only |

### Phase 2+

| Approach | Detail |
|----------|--------|
| Vault | HashiCorp Vault or cloud secret manager |
| Dynamic DB creds | Optional for break-glass accounts |
| Audit | Secret access logged |

### Rotation Runbook

1. Generate new secret in provider
2. Dual-write period (old + new valid)
3. Deploy updated `.env`
4. Revoke old credential
5. Verify audit logs

## 8. Region Configuration

```php
// config/scp.php (conceptual)
'primary_region' => env('SCP_PRIMARY_REGION', 'af-ng-lagos'),
'regions' => [
    'af-ng-lagos' => [
        'timezone' => 'Africa/Lagos',
        'currency_default' => 'NGN',
        'data_residency' => true,
    ],
    'af-ke-nairobi' => [
        'timezone' => 'Africa/Nairobi',
        'currency_default' => 'KES',
        'data_residency' => true,
    ],
],
```

Tenant `primary_region` set at signup; determines default origin routing (Phase 2).

## 9. Local Development

Docker Compose `docker-compose.yml` + `docker-compose.override.yml` (gitignored personal overrides).

```bash
cp .env.example .env
docker compose up -d
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan octane:frankenphp --watch
```

**Parity rule:** Local uses FrankenPHP Octane, not `artisan serve`, to catch Octane-specific bugs early.

## 10. Config Caching

| Environment | `config:cache` | `route:cache` |
|-------------|----------------|---------------|
| local | No | No |
| staging | Yes | Yes |
| production | Yes | Yes |

Deploy script runs cache commands inside container after env injection.

## 11. Security Considerations

- Separate R2 keys per environment; staging cannot write to prod bucket
- Staging database must not contain unredacted production PII without DPO approval
- Cloudflare API tokens scoped minimum permission
- `APP_DEBUG=false` in staging and production (enforced by deploy check)

## 12. Observability

- Log `APP_ENV`, `GIT_SHA`, `SCP_PRIMARY_REGION` on startup
- Alert if production container starts with `APP_DEBUG=true`

## 13. Acceptance Criteria

- [ ] `.env.example` documents all required variables without secrets
- [ ] gitleaks passes on repository history
- [ ] Staging topology matches production service list
- [ ] Secret rotation drill completed once
- [ ] Production deploy fails if `APP_DEBUG=true`

## 14. Sources

- ADR-007: [Secrets Management](../00-meta/adr/007-secrets-management.md)
- Laravel Configuration: https://laravel.com/docs/configuration
- SOPS: https://github.com/getsops/sops
