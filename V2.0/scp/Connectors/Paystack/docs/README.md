# Paystack Connector

**Package:** `sapphital/connector-paystack`  
**Version:** 0.1.0  
**Layer:** Connector (PSP adapter)  
**Traceability:** SCP-TASK-0009, ADR-007, ADR-023, Platform OS Ch. 13

## Purpose

Paystack payment service provider adapter for transaction initialization, verification, and webhook signature validation. Phase 1 ships stub responses when credentials are absent; live HTTP integration follows in a later task.

## Configuration (ADR-007)

PSP credentials are injected via encrypted environment variables at deploy time — never committed to source control (NFR-045).

| Env var | Config key | Required |
|---------|------------|----------|
| `PAYSTACK_SECRET_KEY` | `paystack.secret_key` | Production / webhook HMAC verification |
| `PAYSTACK_PUBLIC_KEY` | `paystack.public_key` | Storefront checkout (Phase 2) |

`PaystackServiceProvider` merges `config/paystack.php`, which maps env vars:

```php
'secret_key' => env('PAYSTACK_SECRET_KEY'),
'public_key' => env('PAYSTACK_PUBLIC_KEY'),
```

`PaystackConnector` reads `config('paystack.secret_key')` for webhook HMAC verification. When the secret key is empty, the connector runs in **stub mode** (testing and local dev without live credentials).

## Interface

`PaystackConnectorInterface` exposes:

- `initializeTransaction(array $payload): array`
- `verifyTransaction(string $reference): array`
- `verifyWebhookSignature(string $payload, string $signature): bool`
- `handleWebhook(array $payload): array`

Registered as a singleton via `PaystackServiceProvider`.

## References

- [ADR-007 Secrets Management](../../../docs/00-meta/adr/007-secrets-management.md)
- [Platform OS Ch. 13](../../../docs/03-architecture/13-platform-os-architecture.md)
- [Master Execution Plan SCP-TASK-0009](../../../docs/21-implementation-playbooks/00-master-execution-plan.md)
