# Commerce Orders

SAPPHITAL Commerce — order creation from checkout sessions, tenant-scoped listing, payment status tracking, returns (RMA), and refunds.

## Phase 1 endpoints

| Method | Path | Auth |
|--------|------|------|
| GET | `/api/v1/commerce/orders` | Merchant |
| GET | `/api/v1/commerce/orders/{id}` | Tenant |
| POST | `/api/v1/commerce/orders/{id}/refund` | Merchant (idempotent) |
| GET | `/api/v1/commerce/returns` | Merchant |
| POST | `/api/v1/commerce/returns` | Merchant |
| POST | `/api/v1/commerce/returns/{id}/approve` | Merchant (idempotent) |
| POST | `/api/v1/commerce/returns/{id}/reject` | Merchant |
