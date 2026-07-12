# Chapter 07: Success Metrics

## Measurement Framework

SCP success is measured across four dimensions: **Platform Health**, **Merchant Success**, **Customer Experience**, and **Business Growth**.

---

## North Star Metric

> **Gross Merchandise Value (GMV)** — Total value of merchandise sold through SCP-powered stores per month.

GMV aligns platform success with merchant success: we grow when our merchants grow.

**Year 1 target:** $1M GMV/month  
**Year 3 target:** $50M GMV/month  
**Year 5 target:** $500M GMV/month

---

## Platform Health Metrics

| Metric | Definition | Year 1 Target | Year 3 Target |
|--------|-----------|---------------|---------------|
| **Uptime** | Platform availability (excl. planned maintenance) | 99.9% | 99.95% |
| **API p95 latency (read)** | 95th percentile response time for GET endpoints | ≤ 200ms | ≤ 100ms |
| **API p95 latency (write)** | 95th percentile response time for POST/PUT endpoints | ≤ 500ms | ≤ 300ms |
| **Error rate** | 5xx responses / total responses | ≤ 0.1% | ≤ 0.05% |
| **Deployment frequency** | Production deployments per week | ≥ 2 | ≥ 5 |
| **Mean time to recovery (MTTR)** | Average time to resolve P1 incidents | ≤ 30 min | ≤ 15 min |
| **Test coverage** | Automated test coverage (backend) | ≥ 80% | ≥ 90% |

---

## Merchant Success Metrics

| Metric | Definition | Year 1 Target | Year 3 Target |
|--------|-----------|---------------|---------------|
| **Active merchants** | Merchants with ≥ 1 order in last 30 days | 500 | 10,000 |
| **Store activation rate** | Merchants who complete setup / total signups | ≥ 60% | ≥ 75% |
| **Time to first sale** | Median days from signup to first order | ≤ 7 days | ≤ 3 days |
| **Time to launch** | Median minutes from signup to live store | ≤ 15 min | ≤ 10 min |
| **Merchant retention (M3)** | Merchants active at month 3 / month 1 cohort | ≥ 50% | ≥ 70% |
| **Merchant retention (M12)** | Merchants active at month 12 / month 1 cohort | ≥ 30% | ≥ 50% |
| **Merchant NPS** | Net Promoter Score survey | ≥ 40 | ≥ 60 |
| **AI feature adoption** | Merchants using ≥ 1 AI feature / total active | ≥ 40% | ≥ 70% |
| **Average merchant GMV** | GMV / active merchants | $500/mo | $2,000/mo |

---

## Customer Experience Metrics

| Metric | Definition | Year 1 Target | Year 3 Target |
|--------|-----------|---------------|---------------|
| **Storefront LCP** | Largest Contentful Paint (p75, mobile) | ≤ 2.0s | ≤ 1.5s |
| **Checkout completion rate** | Completed checkouts / initiated checkouts | ≥ 65% | ≥ 75% |
| **Cart abandonment rate** | Abandoned carts / total carts | ≤ 70% | ≤ 60% |
| **Search success rate** | Searches leading to product click / total searches | ≥ 70% | ≥ 85% |
| **Mobile traffic share** | Mobile sessions / total sessions | ≥ 60% | ≥ 70% |
| **Customer support resolution** | AI-resolved queries / total queries | ≥ 50% | ≥ 80% |
| **Return rate** | Returned orders / total orders | ≤ 5% | ≤ 3% |

---

## Business Growth Metrics

| Metric | Definition | Year 1 Target | Year 3 Target |
|--------|-----------|---------------|---------------|
| **MRR** | Monthly Recurring Revenue (subscriptions) | $5K | $250K |
| **ARR** | Annual Recurring Revenue | $60K | $3M |
| **Revenue per merchant** | Total revenue / active merchants | $10/mo | $25/mo |
| **Free → Paid conversion** | Merchants upgrading from free tier | ≥ 8% | ≥ 12% |
| **Churn rate (monthly)** | Merchants canceling / total paid merchants | ≤ 5% | ≤ 3% |
| **LTV:CAC ratio** | Lifetime value / customer acquisition cost | ≥ 3:1 | ≥ 5:1 |
| **Transaction revenue** | Revenue from payment processing fees | $2K/mo | $100K/mo |
| **Marketplace GMV share** | Revenue from marketplace commissions | — | $50K/mo |

---

## Technical Quality Metrics

| Metric | Definition | Target |
|--------|-----------|--------|
| **Core Web Vitals pass rate** | Storefronts passing all CWV thresholds | ≥ 90% of stores |
| **Lighthouse performance score** | Average performance score across storefronts | ≥ 90 |
| **Accessibility score** | WCAG 2.2 AA compliance (automated audit) | 100% of platform UI |
| **Security vulnerabilities** | Critical/high vulns in production | 0 |
| **Database query p95** | 95th percentile query execution time | ≤ 50ms |
| **Cache hit rate** | Redis cache hits / total cache requests | ≥ 85% |
| **Queue processing time** | p95 job processing latency | ≤ 5s |
| **Test suite duration** | Full CI pipeline execution time | ≤ 15 min |

---

## AI Platform Metrics

| Metric | Definition | Year 1 Target | Year 3 Target |
|--------|-----------|---------------|---------------|
| **AI query volume** | AI requests processed per day | 1,000 | 100,000 |
| **AI response quality** | User satisfaction rating on AI responses | ≥ 4.0/5 | ≥ 4.5/5 |
| **AI cost per tenant** | Average AI infrastructure cost per active tenant | ≤ $0.50/mo | ≤ $1.00/mo |
| **AI task completion rate** | AI agent tasks completed without human intervention | ≥ 60% | ≥ 85% |
| **RAG accuracy** | Correct answers from RAG pipeline / total queries | ≥ 80% | ≥ 92% |

---

## Reporting Cadence

| Report | Audience | Frequency | Contents |
|--------|----------|-----------|----------|
| **Platform dashboard** | Engineering | Real-time | Uptime, latency, errors, queue depth |
| **Merchant health** | Product | Weekly | Activation, retention, GMV, NPS |
| **Business review** | Leadership | Monthly | MRR, churn, LTV:CAC, GMV |
| **Security review** | Engineering + Leadership | Monthly | Vulnerabilities, incidents, audit findings |
| **AI performance** | Product + Engineering | Weekly | Query volume, quality, cost, completion rate |

---

## Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-020 | Platform must provide real-time operational dashboard for engineering team | P0 |
| NFR-021 | Platform must track and report all metrics defined in this chapter | P0 |
| NFR-022 | Merchant analytics dashboard must be available from Business tier | P1 |
| NFR-023 | Platform must alert on-call engineer when uptime drops below 99.9% | P0 |
