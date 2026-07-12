# Chapter 09: Non-Functional Requirements

Non-functional requirements (NFRs) define **how well** the system performs its functions. These are binding constraints that every module must satisfy.

---

## Performance Requirements

| ID | Requirement | Target | Measurement |
|----|-------------|--------|-------------|
| NFR-001 | Storefront page load (LCP, mobile p75) | ≤ 2.0 seconds | Lighthouse CI, CrUX |
| NFR-002 | Storefront page load (LCP, desktop p75) | ≤ 1.5 seconds | Lighthouse CI |
| NFR-003 | API read endpoint latency (p95) | ≤ 200ms | OpenTelemetry traces |
| NFR-004 | API write endpoint latency (p95) | ≤ 500ms | OpenTelemetry traces |
| NFR-005 | Search autocomplete latency (p95) | ≤ 100ms | Application metrics |
| NFR-006 | Admin dashboard initial load (TTI) | ≤ 3.0 seconds | Lighthouse CI |
| NFR-007 | Database query execution (p95) | ≤ 50ms | PostgreSQL pg_stat |
| NFR-008 | Background job processing (p95) | ≤ 5 seconds | Horizon metrics |
| NFR-009 | Storefront JavaScript bundle (initial, gzipped) | ≤ 150 KB | Webpack bundle analyzer |
| NFR-010 | Storefront CSS bundle (gzipped) | ≤ 50 KB | Webpack bundle analyzer |
| NFR-011 | Image delivery (product page hero, WebP) | ≤ 200 KB | CDN analytics |
| NFR-012 | Checkout flow completion time (user perspective) | ≤ 60 seconds | Analytics funnel |

---

## Scalability Requirements

| ID | Requirement | Phase 1 Target | Phase 3 Target |
|----|-------------|----------------|----------------|
| NFR-013 | Concurrent users (platform-wide) | 1,000 | 50,000 |
| NFR-014 | Active merchants | 500 | 10,000 |
| NFR-015 | Products per store | 10,000 | 100,000 |
| NFR-016 | Orders per day (platform-wide) | 1,000 | 100,000 |
| NFR-017 | API requests per second (platform-wide) | 100 | 5,000 |
| NFR-018 | Storage per tenant | 5 GB | 200 GB |
| NFR-019 | Search index size | 1M documents | 100M documents |
| NFR-020 | Webhook deliveries per minute | 100 | 10,000 |

**Scaling strategy:**

- Phase 1: Vertical scaling (single server, Octane, Redis cache)
- Phase 2: Read replicas, CDN, queue workers horizontal scaling
- Phase 3: Service extraction (search, AI, notifications), load balancer
- Phase 4: Multi-region, database sharding, Kubernetes

---

## Availability & Reliability

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-021 | Platform uptime (monthly) | 99.9% (≤ 43 min downtime) |
| NFR-022 | Planned maintenance window | ≤ 2 hours/month, off-peak |
| NFR-023 | Mean time to recovery (MTTR) for P1 incidents | ≤ 30 minutes |
| NFR-024 | Mean time between failures (MTBF) | ≥ 720 hours |
| NFR-025 | Data backup frequency | Every 6 hours (automated) |
| NFR-026 | Backup recovery time objective (RTO) | ≤ 4 hours |
| NFR-027 | Backup recovery point objective (RPO) | ≤ 6 hours |
| NFR-028 | Zero-downtime deployments | Required from Phase 2 |

---

## Security Requirements

| ID | Requirement | Standard | Phase |
|----|-------------|----------|-------|
| NFR-029 | Application security verification | OWASP ASVS **5.0** Level 2 | Phase 1 |
| NFR-030 | Transport encryption | TLS 1.3 minimum | Phase 1 |
| NFR-031 | Data encryption at rest | AES-256 (database, storage) | Phase 1 |
| NFR-032 | Password storage | Argon2id (preferred) or bcrypt (cost ≥ 12) | Phase 1 |
| NFR-033 | Session management | Secure, HttpOnly, SameSite cookies; 24h expiry | Phase 1 |
| NFR-034 | CSRF protection | Token-based on all state-changing requests | Phase 1 |
| NFR-035 | Content Security Policy | Strict CSP on all web surfaces | Phase 1 |
| NFR-036 | Rate limiting | Per-IP and per-tenant on all public endpoints | Phase 1 |
| NFR-037 | Input validation | Server-side validation on all inputs; allowlist approach | Phase 1 |
| NFR-038 | SQL injection prevention | Parameterized queries only (Eloquent ORM) | Phase 1 |
| NFR-039 | XSS prevention | Output encoding; CSP; no inline scripts | Phase 1 |
| NFR-040 | Tenant isolation | Zero cross-tenant data access (verified by automated tests) | Phase 1 |
| NFR-041 | Audit logging | All authentication, authorization, and data modification events | Phase 1 |
| NFR-042 | Dependency vulnerability scanning | Automated in CI/CD; zero critical/high in production | Phase 1 |
| NFR-043 | Penetration testing | Before Phase 2 launch and annually thereafter | Phase 2 |
| NFR-044 | PCI DSS compliance | SAQ A (hosted checkout — no card data on platform) | Phase 1 |
| NFR-045 | Secrets management | Vault or encrypted env; never in source code | Phase 1 |
| NFR-046 | Bot detection | WAF + rate limiting + Turnstile on sensitive endpoints | Phase 1 |
| NFR-083 | Nigeria NDPA compliance | NDPC registration (DCPMI), DPO, RoPA, 72h breach notification, cross-border transfer records | Phase 1 |
| NFR-084 | Kenya DPA compliance | ODPC registration (controller + processor), RoPA, 72h breach notification | Phase 1 (Kenya launch) |
| NFR-085 | Pan-Africa privacy framework | Unified consent, export, deletion, and subprocessor disclosure across operating countries | Phase 1 |

---

## Accessibility Requirements

| ID | Requirement | Standard | Phase |
|----|-------------|----------|-------|
| NFR-047 | Web accessibility | WCAG 2.2 Level AA | Phase 1 |
| NFR-048 | Keyboard navigation | All admin workflows operable via keyboard | Phase 1 |
| NFR-049 | Screen reader compatibility | Checkout flow tested with NVDA/VoiceOver | Phase 1 |
| NFR-050 | Color contrast ratio | ≥ 4.5:1 (text), ≥ 3:1 (large text, UI components) | Phase 1 |
| NFR-051 | Touch target size | ≥ 44×44px on mobile | Phase 1 |
| NFR-052 | Motion sensitivity | Respect `prefers-reduced-motion` | Phase 1 |
| NFR-053 | Form accessibility | Labels, error messages, focus management | Phase 1 |

---

## Compatibility Requirements

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-054 | Mobile browsers | Chrome 90+, Safari 15+, Samsung Internet 16+ |
| NFR-055 | Desktop browsers | Chrome 90+, Firefox 90+, Safari 15+, Edge 90+ |
| NFR-056 | Mobile OS | Android 10+, iOS 15+ |
| NFR-057 | Screen sizes | 320px – 2560px width |
| NFR-058 | Network conditions | Functional on 3G (768 Kbps); optimized for 4G |
| NFR-059 | PHP version | 8.4+ |
| NFR-060 | PostgreSQL version | 16+ |
| NFR-061 | Node.js version | 20 LTS+ |

---

## Observability Requirements

| ID | Requirement | Tool | Phase |
|----|-------------|------|-------|
| NFR-062 | Structured logging | JSON format, Monolog → centralized log store | Phase 1 |
| NFR-063 | Application metrics | Prometheus-compatible (request rate, latency, errors) | Phase 1 |
| NFR-064 | Distributed tracing | OpenTelemetry (trace ID across API → DB → queue) | Phase 1 |
| NFR-065 | Health check endpoints | `/health` (liveness), `/ready` (readiness) per service | Phase 1 |
| NFR-066 | Error tracking | Sentry or equivalent; automatic exception capture | Phase 1 |
| NFR-067 | Uptime monitoring | External synthetic monitoring (1-min intervals) | Phase 1 |
| NFR-068 | Alerting | PagerDuty/equivalent for P1/P2 incidents | Phase 1 |
| NFR-069 | Business metrics dashboard | GMV, orders, signups, conversion (real-time) | Phase 2 |
| NFR-070 | Log retention | 90 days hot, 1 year cold storage | Phase 1 |

---

## Data Requirements

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-071 | Data residency (Phase 1) | Nigeria (Lagos region primary); Kenya/East Africa for KE merchants; subprocessors documented per NDPA §41–43 and Kenya DPA §48–50 |
| NFR-072 | GDPR readiness | Data export, deletion, consent management | Phase 3 |
| NFR-073 | Data retention policy | Transaction data: 7 years; logs: 1 year; analytics: 3 years |
| NFR-074 | Soft deletes | All tenant-scoped entities support soft delete with recovery window |
| NFR-075 | Audit trail | Immutable audit log for financial and identity operations |
| NFR-076 | Database migrations | Zero-downtime migrations; backward-compatible schema changes |
| NFR-077 | Data export | Merchant can export all their data (JSON/CSV) on demand |

---

## Internationalization Requirements

| ID | Requirement | Phase |
|----|-------------|-------|
| NFR-078 | Multi-currency support | Phase 1 (NGN, KES, GHS, USD) |
| NFR-079 | Multi-language UI | Phase 1 (English; Hausa, Yoruba, Igbo Phase 1.5; Swahili for Kenya) |
| NFR-080 | RTL language support | Phase 4 (Arabic) |
| NFR-081 | Locale-aware formatting | Phase 1 (dates, numbers, currency) |
| NFR-082 | Timezone handling | Phase 1 (tenant timezone for all timestamps) |

---

## Compliance Matrix

| Standard | Applicable NFRs | Compliance Level | Audit |
|----------|----------------|------------------|-------|
| ISO/IEC 25010 (Performance) | NFR-001 – NFR-012 | Self-assessed | Continuous monitoring |
| ISO/IEC 25010 (Reliability) | NFR-021 – NFR-028 | Self-assessed | Monthly review |
| ISO/IEC 25010 (Security) | NFR-029 – NFR-046 | OWASP ASVS L2 | Phase 2 pentest |
| WCAG 2.2 AA | NFR-047 – NFR-053 | Automated + manual audit | Per release |
| PCI DSS SAQ A | NFR-044 | Self-assessed | Annual |
| Nigeria NDPA + GAID | NFR-083, NFR-085 | NDPC registration + controls | Annual CAR (if DCPMI) |
| Kenya DPA | NFR-084 | ODPC registration | At Kenya launch |
| ISO/IEC 25010 (Maintainability) | Engineering principles | Modular monolith | Architecture review |

---

## NFR Verification

Every module specification (Volume 5+) must include an **NFR Compliance** section:

```markdown
## NFR Compliance

| NFR ID | Applicable | Compliance Approach | Test Method |
|--------|-----------|--------------------|-|
| NFR-003 | Yes | Redis cache for product reads | Load test |
| NFR-040 | Yes | tenant_id scope on all queries | Isolation test suite |
| ... | ... | ... | ... |
```

NFR compliance is verified in CI/CD pipeline and during quarterly architecture reviews.
