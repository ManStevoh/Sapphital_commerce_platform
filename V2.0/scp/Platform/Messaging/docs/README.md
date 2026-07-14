# Platform Messaging

**Package:** `Platform/Messaging`  
**Version:** 0.1.0  
**Layer:** Platform Kernel  
**Traceability:** SCP-DB-001-06, SCP-ARCH-001-07, SCP-DEV-001-04, Phase 2 §5.2

## Purpose

Transactional outbox for guaranteed delivery of integration events to merchant webhook endpoints.

## Components

| Piece | Role |
|-------|------|
| `platform_outbox` | Unpublished domain/integration events |
| `platform_outbox_dead` | Exhausted retries |
| `webhook_endpoints` | Tenant subscription URLs + topics |
| `webhook_deliveries` | Per-endpoint idempotent delivery log |
| `messaging:poll-outbox` | Poller → signed HTTP delivery |

## Delivery

- At-least-once; consumers must be idempotent (`SCP-Event-Id`)
- HMAC `SCP-Signature: t=…,v1=…`
- Exponential backoff; max 10 retries then dead letter
