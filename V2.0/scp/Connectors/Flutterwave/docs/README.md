# Flutterwave connector

SAPPHITAL PSP connector for Flutterwave hosted checkout (Nigeria GA Phase 1).

## Config

`FlutterwaveServiceProvider` merges `config/flutterwave.php`:

| Env var | Purpose |
|---------|---------|
| `FLUTTERWAVE_SECRET_KEY` | API bearer token |
| `FLUTTERWAVE_PUBLIC_KEY` | Client-side key (future) |
| `FLUTTERWAVE_SECRET_HASH` | Webhook `verif-hash` validation |

Stub mode is active in `testing` or when `FLUTTERWAVE_SECRET_KEY` is empty.

## Webhook

`POST /api/v1/webhooks/flutterwave` with header `verif-hash`.

Events handled: `charge.completed` → checkout reconciliation (same reference column as Paystack).
