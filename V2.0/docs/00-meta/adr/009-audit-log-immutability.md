# ADR-009: Audit Log Storage and Immutability

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 11 — Security

## Context

Nigeria NDPA (§40), Kenya DPA, PCI SAQ A, and NFR-041/075 require reliable audit trails for authentication, financial operations, and data access. Logs must support NDPC Compliance Audit Returns and ODPC breach investigations.

## Decision

**Phase 1:**

- Append-only `audit_logs` table in PostgreSQL (no UPDATE/DELETE grants for app role)
- Fields: `id (UUIDv7), tenant_id, actor_type, actor_id, impersonator_id, action, resource_type, resource_id, before, after (encrypted if PII), ip, user_agent, request_id, created_at`
- Mandatory events per Volume 11 §3.8
- Retention: 90 days hot (NFR-070), 1 year cold; financial events 7 years (NFR-073)

**Phase 2:**

- Stream to WORM object storage (S3/R2 with object lock) for tamper evidence
- SIEM alerting on authz anomalies and cross-tenant attempt signatures

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected (Phase 1) |
|-------------|------|------|------------------------|
| External SIEM only | Rich analytics | Cost; dependency | Phase 2 supplement |
| Mutable application logs | Simple | Fails repudiation requirements | Rejected |

## References

- NFR-041, NFR-075, NFR-078, NFR-079
