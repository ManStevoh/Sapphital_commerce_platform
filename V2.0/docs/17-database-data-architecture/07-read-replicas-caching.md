# Chapter 07: Read Replicas & Caching

**Document ID:** SCP-DB-001-07  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-062, NFR-066  

---

## Purpose

Define use of **PostgreSQL read replicas** and **Redis caching** without breaking RLS or consistency guarantees.

---

## 1. Read Replica Routing

| Query type | Target | Lag tolerance |
|------------|--------|---------------|
| Checkout, inventory hold | Primary | 0 |
| Admin order detail after write | Primary | 0 |
| Merchant dashboard aggregates | Replica | ≤ 30s |
| Export CSV / BI | Replica | ≤ 5 min |
| Platform admin reports | Replica | ≤ 5 min |

Laravel read/write connection split; sticky session after write for same request.

---

## 2. RLS on Replicas

Replicas inherit same roles and policies. Application **must** set `SET LOCAL` on replica connections identically.

---

## 3. Redis Cache Layers

| Key pattern | TTL | Invalidation |
|-------------|-----|--------------|
| `tenant:{id}:settings` | 5 min | Webhook on settings change |
| `product:{tenant}:{handle}` | 2 min | Product update event |
| `theme:{tenant}:active` | 10 min | Theme publish |
| `rate_limit:{ip}` | 1 min | Sliding window |
| `idempotency:{key}` | 24 h | Payment webhooks |

Cache keys **always** include `tenant_id`.

---

## 4. Cache Stampede Protection

- Probabilistic early expiration
- Single-flight lock via Redis `SET NX`
- Null caching for missing products (short TTL)

---

## 5. Nigeria Latency

Redis co-located with API in Lagos. CDN caches storefront HTML (Volume 10); Redis caches hot API reads for admin mobile apps.

---

## Cross-References

- [Volume 10 Ch. 06 — Caching](../10-infrastructure/06-caching-and-redis.md)
- [Chapter 08 — Analytics Pipeline](./08-analytics-pipeline-olap.md)
