# Chapter 06: Event Outbox & Audit Tables

**Document ID:** SCP-DB-001-06  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-009, Volume 3 Ch. 07  

---

## Purpose

Specify **transactional outbox** for domain events and **immutable audit** storage.

---

## 1. Outbox Table

```sql
CREATE TABLE platform_outbox (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  tenant_id UUID NOT NULL,
  aggregate_type TEXT NOT NULL,
  aggregate_id UUID NOT NULL,
  event_type TEXT NOT NULL,
  payload JSONB NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  published_at TIMESTAMPTZ NULL,
  retry_count INT NOT NULL DEFAULT 0
);

CREATE INDEX idx_outbox_unpublished
  ON platform_outbox (created_at)
  WHERE published_at IS NULL;
```

Events written in **same transaction** as domain change.

---

## 2. Publisher Worker

Horizon job polls unpublished rows (`FOR UPDATE SKIP LOCKED`), publishes to Redis queue / webhook dispatcher, sets `published_at`.

| Setting | Value |
|---------|-------|
| Batch size | 500 |
| Max retries | 10 exponential backoff |
| Dead letter | `platform_outbox_dead` table + alert |

---

## 3. Audit Log (ADR-009)

```sql
CREATE TABLE audit_events (
  id UUID NOT NULL,
  tenant_id UUID NULL,
  actor_id UUID NULL,
  actor_type TEXT NOT NULL,
  action TEXT NOT NULL,
  resource_type TEXT NOT NULL,
  resource_id UUID NULL,
  metadata JSONB NOT NULL DEFAULT '{}',
  ip_address INET NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (id, created_at)
) PARTITION BY RANGE (created_at);
```

- **Append-only** — UPDATE/DELETE revoked for app role
- Hash chain optional Phase 3 for tamper evidence
- Retention: 7 years financial-adjacent; 2 years general admin (Chapter 10)

---

## 4. Event Catalog (Representative)

| Event | Source | Consumers |
|-------|--------|-----------|
| `OrderPaid` | Commerce | Analytics, email, webhooks |
| `InventoryAdjusted` | Commerce | Search index, low-stock alerts |
| `ContentReleasePublished` | CMS | CDN purge, webhooks |
| `EnrollmentGranted` | Learning | Email, progress tracker |
| `VendorPayoutCreated` | Marketplace | Accounting export |

Full catalog in Volume 12 webhooks chapter.

---

## Cross-References

- [Volume 3 Ch. 07 — Event-Driven Communication](../03-architecture/07-event-driven-communication.md)
- ADR-009
