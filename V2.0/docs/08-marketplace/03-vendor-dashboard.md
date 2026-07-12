# Chapter 03: Vendor Dashboard

**Document ID:** SCP-MKT-001-03  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** FR-006, NFR-006, NFR-047, NFR-048  

---

## 1. Purpose

Define the vendor-facing portal where sellers manage their marketplace presence: profile, products, orders, payouts, disputes, and performance metrics — consistent with SCP admin UX (Volume 4) and isolated by vendor context.

## 2. Scope

- Vendor portal information architecture
- Role-based views for vendor_owner and vendor_staff
- Mobile-responsive layouts for Nigeria connectivity (3G per NFR-058)
- Integration points with catalog, orders, and payouts
- Operator vs vendor dashboard boundaries

## 3. Out of Scope

- Operator marketplace admin (covered across chapters; primary UI in operator admin app)
- Theme customization of vendor micro-storefront (Volume 6, Phase 2)

## 4. User & Business Value

Vendors on Nigerian marketplaces (Jumia, Konga, local WhatsApp commerce) expect self-service. SCP vendor portal eliminates operator-as-middleman for routine tasks, reducing Fatima's 3-day/month reconciliation burden to automated visibility.

## 5. Portal Architecture

Vendor portal is a **Next.js** application route group at `/vendor/*`, authenticated via Sanctum with `vendor_id` claim in session/token.

```mermaid
graph LR
    subgraph VendorPortal["Vendor Portal (Next.js)"]
        HOME[Dashboard Home]
        PROD[Products]
        ORD[Orders]
        PAY[Payouts]
        DISP[Disputes]
        SET[Settings]
    end

    subgraph API["SCP API"]
        VAPI[/api/v1/vendor/*]
    end

    HOME & PROD & ORD & PAY & DISP & SET --> VAPI
    VAPI --> RLS[RLS vendor_id scope]
```

Middleware chain:

1. Authenticate user
2. Resolve `vendor_id` from membership
3. `SET LOCAL app.vendor_id` for RLS (ADR-005)
4. Reject if vendor `status != active` (except settings/KYC resubmit)

## 6. Navigation & IA

| Section | Route | vendor_staff | vendor_owner |
|---------|-------|:------------:|:------------:|
| Dashboard | `/vendor` | Read | Full |
| Products | `/vendor/products` | CRUD | CRUD |
| Orders | `/vendor/orders` | Fulfill | Full |
| Payouts | `/vendor/payouts` | Read | Full |
| Disputes | `/vendor/disputes` | Respond | Full |
| Analytics | `/vendor/analytics` | Read | Full |
| Settings | `/vendor/settings` | — | Full |
| Team | `/vendor/team` | — | Full |

## 7. Dashboard Home

### 7.1 KPI Cards (NGN default)

| Card | Calculation | Period |
|------|-------------|--------|
| Gross sales | Sum order line totals (vendor) | Today / 7d / 30d |
| Net earnings | Gross − commissions − fees | Same |
| Orders | Count fulfilled + pending | Same |
| Open disputes | Count `status = open` | Current |

### 7.2 Action Queue

| Item | Condition | CTA |
|------|-----------|-----|
| Orders to ship | `fulfillment_status = unfulfilled` AND SLA breach < 24h | View orders |
| Low stock alerts | Variant `available < threshold` | Restock |
| Payout pending | Next payout date + amount | View payouts |
| KYC expiring | `expires_at` within 30 days | Update KYC |
| Dispute response due | SLA timer | Respond |

### 7.3 Trust Score Widget

Display score 0–100 with breakdown (Chapter 06): fulfillment rate, response time, dispute rate, cancellation rate.

## 8. Products View

Vendor-scoped product list with filters: status (`draft`, `pending_review`, `published`, `rejected`, `suspended`), category, stock.

Actions:

- Create product → enters operator moderation if `marketplace.require_listing_approval = true`
- Bulk import CSV (max 200 SKUs/job)
- Bulk price update (percentage or fixed NGN)

**Rule:** Vendor cannot assign products to another vendor_id.

## 9. Orders View

List vendor sub-orders only (`order_vendor_splits` where `vendor_id = current`).

| Column | Visible to Vendor |
|--------|-------------------|
| Sub-order ID | Yes |
| Date | Yes |
| Customer name | Yes (fulfillment) |
| Customer phone | Yes (fulfillment) |
| Customer email | Masked (`a***@domain.com`) unless operator enables full |
| Shipping address | Yes |
| Line items | Own lines only |
| Commission deducted | Yes |
| Net amount | Yes |

Fulfillment actions:

- Mark packed
- Add tracking number (carrier + tracking ID)
- Mark shipped / delivered
- Request cancellation (operator approval if paid)

## 10. Payouts View

| Element | Description |
|---------|-------------|
| Balance summary | Pending, available, on-hold (NGN, integer kobo display) |
| Payout schedule | Next run date (operator config: weekly default) |
| Payout history | Table: period, gross, commission, fees, net, status, bank last-4 |
| Export | CSV download for vendor accounting |

Hold reasons displayed in plain language:

- Order within return window (7 days default)
- Open dispute
- Bank account change cooling period
- Operator manual hold

## 11. Disputes View

List disputes linked to vendor sub-orders. Vendor can:

- Upload evidence (photos, chat logs, delivery proof)
- Accept resolution (refund)
- Contest with statement (5000 char max)

SLA countdown visible: respond within **48 hours** or auto-escalate to operator.

## 12. Settings

| Tab | Contents |
|-----|----------|
| Profile | Business name, logo, description, slug |
| KYC | Status, re-upload, expiry |
| Bank account | View masked account; change triggers MFA + hold |
| Notifications | Email, SMS, WhatsApp preferences (opt-in per NDPA) |
| API keys | Phase 2 — vendor-scoped read-only tokens |

## 13. Team Management (vendor_owner)

Invite `vendor_staff` by email. Permissions fixed bundle in Phase 1:

- **Fulfillment only:** orders + inventory
- **Catalog + fulfillment:** products + orders

Max 5 staff per vendor (Phase 1); configurable by operator plan.

## 14. Customer PII Minimization (NDPA)

Vendor dashboard implements **data minimization** per NDPA §35:

| Field | Rationale |
|-------|-----------|
| Full email | Hidden by default; reduces marketing poaching |
| Phone | Shown for delivery coordination |
| Address | Shown for shipping |
| Payment method | Never shown |
| Customer order history with other vendors | Never shown |

Audit: any unmask email action logged if operator enables `marketplace.vendor_full_customer_email = true`.

## 15. UI/UX Requirements

| Requirement | Standard |
|-------------|----------|
| Mobile layout | Single column; bottom nav on ≤768px |
| Touch targets | ≥ 44×44px (NFR-051) |
| Accessibility | WCAG 2.2 AA (NFR-047) |
| Currency display | `₦1,234.56` — always NGN in Phase 1 Nigeria |
| Offline | Not supported Phase 1; show connection banner |
| Language | English Phase 1; Hausa/Yoruba/Igbo Phase 2 |

## 16. Performance

| Surface | Target |
|---------|--------|
| Dashboard home API | p95 ≤ 300ms |
| Order list (50 rows) | p95 ≤ 200ms |
| Initial portal load TTI | ≤ 3.0s on 4G |

Cache vendor KPIs in Redis with 60s TTL; invalidate on order/payout events.

## 17. Failure Modes

| Failure | User Experience |
|---------|-----------------|
| Vendor suspended | Redirect to suspension notice; read-only payouts history |
| PSP subaccount missing | Banner: "Payout setup incomplete"; link to support |
| Token vendor_id mismatch | 403 + force re-login |
| Partial API failure | KPI cards show skeleton + retry |

## 18. Acceptance Criteria

1. Vendor sees only own products, orders, payouts, disputes.
2. Customer email masked by default; phone and address visible for fulfillment.
3. Dashboard KPIs match ledger totals within 1 kobo.
4. Mobile layout passes WCAG 2.2 AA spot check on orders flow.
5. vendor_staff permissions enforced on all routes.

## 19. Sources

- Volume 4 Design System (component reuse)
- Volume 1 Persona: Fatima vendor self-service requirements
- NDPA data minimization: https://ndpc.gov.ng/
