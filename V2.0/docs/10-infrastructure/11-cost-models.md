# Chapter 11: Cost Models

**Document ID:** SCP-INF-001-11  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-013, ADR-011, Volume 16 SaaS plans

---

## Purpose

Provide **infrastructure cost models** by growth phase so SCP maintains viable unit economics for Nigeria-priced SaaS plans (₦15,000–₦150,000/month) while scaling to 10,000+ merchants.

## Scope

- Cost breakdown by service (compute, DB, Redis, CDN, R2, Meilisearch, observability)
- Per-merchant infrastructure cost targets
- Phase 1–4 monthly spend bands
- Cost optimization levers
- Nigeria NGN vs USD infra exposure
- Alerting on budget overrun

## Out of Scope

- Revenue and gross margin modeling (finance)
- Payment processing fees (Volume 5)
- Employee salaries

---

## 1. Cost Philosophy

| Principle | Implementation |
|-----------|----------------|
| Unit economics first | Infra cost per active merchant must support starter plan margin |
| Pay for usage | Scale vertically before horizontally; R2 not EBS |
| Edge reduces origin | Cloudflare CDN cuts Lagos egress |
| Managed when cheap | Managed PostgreSQL when ops cost > savings |
| Measure monthly | Cost dashboard tagged by environment |

**Target:** Infrastructure ≤ **25% of ARPU** at scale (Phase 3+).

---

## 2. Cost Components

| Component | Provider (Phase 1) | Billing Unit |
|-----------|-------------------|--------------|
| Compute (VM) | Nigerian/WA cloud VPS | vCPU-hour |
| PostgreSQL | Self-hosted → managed | Storage + IOPS |
| Redis | Self-hosted → managed | Memory |
| Meilisearch | Self-hosted | Compute |
| Cloudflare | Pro/Business | Fixed + usage |
| R2 | Cloudflare | Storage + Class A/B ops |
| Observability | Sentry + self-hosted Prometheus | Events + compute |
| SMS/Email | Termii, Resend | Per message |
| AI inference | OpenAI / Anthropic API | Per token |

---

## 3. Phase Cost Bands (Monthly, USD Equivalent)

Assumptions: 1 USD ≈ ₦1,600 (planning rate); active merchant = ≥ 1 login/month.

### 3.1 Phase 1 — 0–500 Merchants

| Item | Monthly Cost | Notes |
|------|--------------|-------|
| 2× app VMs (8 vCPU/32GB) | $400 | Octane + Horizon + Next.js |
| 1× DB VM (4 vCPU/16GB/500GB) | $200 | PostgreSQL + PgBouncer |
| 1× aux VM (Redis + Meili) | $80 | |
| Cloudflare Pro | $25 | WAF + CDN |
| R2 (500 GB) | $8 | Media |
| Sentry / monitoring | $50 | |
| Backups (R2) | $15 | |
| **Total** | **~$780** | |
| **Per merchant (500)** | **~$1.56** | ~₦2,500 |

### 3.2 Phase 2 — 500–5,000 Merchants

| Item | Monthly Cost |
|------|--------------|
| App tier (4 VMs) | $800 |
| DB primary + replica | $500 |
| Redis Sentinel | $150 |
| Meilisearch dedicated | $120 |
| Cloudflare Business | $200 |
| R2 (5 TB) | $75 |
| Observability | $150 |
| **Total** | **~$2,000** |
| **Per merchant (5,000)** | **~$0.40** | ~₦640 |

### 3.3 Phase 3 — 5,000–10,000 Merchants

| Item | Monthly Cost |
|------|--------------|
| K8s cluster (partial) | $2,500 |
| Managed PostgreSQL | $1,200 |
| Managed Redis | $400 |
| Meilisearch cluster | $300 |
| Cloudflare + R2 (20 TB) | $400 |
| AI worker pool | $500 |
| Observability | $300 |
| **Total** | **~$5,600** |
| **Per merchant (10,000)** | **~$0.56** | ~₦900 |

### 3.4 Phase 4 — 10,000+ Merchants

Enterprise multi-region; **~$0.50–0.80/merchant** at 25,000 merchants with reserved capacity and autoscaling.

---

## 4. Per-Merchant Cost Drivers

| Driver | Weight | Control |
|--------|--------|---------|
| Storefront page views | High | CDN cache ratio > 85% |
| Admin API calls | Medium | Efficient queries, pagination |
| Media storage | Medium | Plan quotas (Volume 16) |
| Search queries | Medium | Meilisearch index size caps |
| AI token usage | Variable | Tenant budgets (Volume 9) |
| Webhook deliveries | Low | Batching, retry limits |
| Email/SMS | Per event | Merchant-configurable templates |

---

## 5. Nigeria-Specific Considerations

| Factor | Impact | Mitigation |
|--------|--------|------------|
| NGN depreciation | USD infra costs rise in NGN | Annual plan pricing review |
| Power/grid instability | Higher UPS/generator hosting cost | Choose tier-3 Lagos DC |
| Expensive international egress | Origin bandwidth costly | Cloudflare orange-cloud |
| Local cloud immaturity | Fewer managed options | Docker-first; evaluate AWS Cape Town |
| Mobile-heavy traffic | More CDN bytes | WebP, aggressive ISR |

---

## 6. Cost Allocation Tags

| Tag | Values |
|-----|--------|
| `env` | local, staging, production |
| `region` | af-ng-lagos, af-ke-nairobi |
| `service` | octane, postgres, redis, meili, r2, cloudflare |
| `tenant_tier` | starter, growth, pro, enterprise |

Dashboard: Grafana cost panel fed by provider APIs + manual Cloudflare invoice.

---

## 7. Optimization Levers

| Lever | Savings | Tradeoff |
|-------|---------|----------|
| Increase CDN TTL | 20–40% origin bandwidth | Staler content; webhook ISR purge |
| Right-size VMs | 10–30% compute | Latency if under-provisioned |
| Archive old orders to R2 | DB storage | Query complexity |
| Reserved instances (1yr) | 15–25% compute | Lock-in |
| Image optimization | 30–50% R2 egress | CPU on upload |
| Off-peak worker scaling | 10% compute | Slower nightly jobs OK |

---

## 8. Budget Alerts

| Alert | Threshold | Action |
|-------|-----------|--------|
| Monthly infra > 120% budget | Phase band ceiling | Freeze non-critical scaling |
| R2 growth > 20% WoW | Per tenant anomaly | Investigate abuse |
| AI spend > ₦500k/month | Platform total | Review model routing |
| CDN egress spike | 2× 7-day avg | Check hotlinking |

PagerDuty P3 to platform on-call; finance notified monthly.

---

## 9. Plan Economics Cross-Check (Volume 16)

| Plan | Price (NGN/mo) | Target Infra Cost | Max % |
|------|----------------|-------------------|-------|
| Starter | ₦15,000 | ≤ ₦3,000 | 20% |
| Growth | ₦45,000 | ≤ ₦6,000 | 13% |
| Pro | ₦150,000 | ≤ ₦15,000 | 10% |
| Enterprise | Custom | Dedicated cell | Negotiated |

---

## 10. Acceptance Criteria

- [ ] Phase 1–3 monthly cost bands with per-merchant targets
- [ ] Components: compute, DB, Redis, Meili, Cloudflare, R2, observability
- [ ] Per-merchant cost ≤ 25% ARPU target at Phase 3
- [ ] Nigeria NGN/USD exposure documented
- [ ] Cost allocation tags defined
- [ ] Budget alert thresholds listed
- [ ] Plan economics cross-check with Volume 16
- [ ] CDN optimization lever documented

---

## References

- [Volume 16 — Plans & Entitlements](../16-saas-multi-tenancy/03-plans-and-entitlements.md)
- [Volume 9 Ch. 10 — AI Cost Controls](../09-ai-platform/10-tenant-isolation-cost-controls.md)
- [Chapter 10 — Kubernetes Scaling](./10-scaling-path-kubernetes.md)
- [Volume 2 Ch. 10 — Technology Risks](../02-market-research/10-technology-roadmap-and-risks.md)
