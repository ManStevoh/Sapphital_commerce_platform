# Volume 14: Operations

**Document ID:** SCP-OPS-001  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Depends On:** Volume 10 (Infrastructure), Volume 11 (Security)  
**Owner:** Sapphital Learning Company  

---

## Purpose

Volume 14 defines **day-2 operations** for SCP — SLOs, incident management, on-call, capacity planning, merchant support, status communications, postmortems, and analytics/database operations for Nigeria-primary production.

## Scope

- SLO/SLA and error budgets
- Incident management and on-call escalation
- Capacity planning
- Database operations
- Merchant support operations
- Status page and stakeholder communications
- Postmortem process
- Operations acceptance criteria
- Database and analytics architecture
- Merchant storefront analytics (page views, funnels, consent)

## Out of Scope

- Infrastructure provisioning (Volume 10)
- Application feature development
- Legal SLA contract text

## Chapters

| # | Chapter | Status |
|---|---------|--------|
| 01 | [Operations Overview](./01-operations-overview.md) | ✅ Active |
| 02 | [SLO, SLA & Error Budgets](./02-slo-sla-error-budgets.md) | ✅ Active |
| 03 | [Incident Management](./03-incident-management.md) | ✅ Active |
| 04 | [On-Call & Escalation](./04-on-call-escalation.md) | ✅ Active |
| 05 | [Capacity Planning](./05-capacity-planning.md) | ✅ Active |
| 06 | [Database Operations](./06-database-operations.md) | ✅ Active |
| 07 | [Merchant Support Operations](./07-merchant-support-operations.md) | ✅ Active |
| 08 | [Status Page & Communications](./08-status-page-communications.md) | ✅ Active |
| 09 | [Postmortems](./09-postmortems.md) | ✅ Active |
| 10 | [Operations Acceptance Criteria](./10-operations-acceptance-criteria.md) | ✅ Active |
| 11 | [Database & Analytics Architecture](./11-database-analytics-architecture.md) | ✅ Active |
| 12 | [Merchant Storefront Analytics](./12-merchant-storefront-analytics.md) | ✅ Active |

## Platform SLO Summary

| SLO | Target |
|-----|--------|
| Availability | 99.9% monthly |
| Storefront LCP (mobile p75) | ≤ 2.0 s |
| API read p95 | ≤ 200 ms |
| SEV1 response | ≤ 15 min |
| RTO / RPO | 4 h / 6 h |

## Related Volumes

- [Volume 10 — Infrastructure](../10-infrastructure/README.md)
- [Volume 11 — Security](../11-security/README.md)
- [Volume 13 — Testing](../13-testing/README.md)

## Acceptance Criteria (Volume Complete)

- [ ] All 12 chapters published
- [ ] On-call rotation live with documented escalation
- [ ] Status page process for SEV1/SEV2
- [ ] Postmortem template in use within 5 business days of SEV1
- [ ] Analytics pipeline documented without blocking OLTP

---

**Sign-off roles:** Lead Architect, Platform engineer (on-call lead), Support lead.
