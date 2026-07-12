# Chapter 03: REST API Specification

**Document ID:** SCP-DEV-001-03  
**Version:** 1.0.0  
**Status:** 📝 Draft  
**Traceability:** PRD-009, NFR-003, NFR-004, NFR-040  

---

## 1. Purpose

Define the **Admin REST API v1** and **Storefront REST API v1** resource catalog, key operations, and representative OpenAPI 3.1 excerpts. The canonical machine-readable specs live in `openapi/`; this chapter is the human-readable contract reference.

## 2. Scope

- Admin API resources (merchant and app operations)
- Storefront API resources (headless commerce)
- Standard operations per resource
- Expandable fields and webhooks linkage
- Sandbox vs live mode behavior

## 3. Out of Scope

- GraphQL (Phase 4)
- Internal gRPC/queue payloads
- PSP (Paystack) inbound webhooks (Volume 5)

## 4. API Mode

| Mode | Token Prefix | Base URL | Data |
|------|--------------|----------|------|
| **Live** | `scp_live_` | `api.sapphital.com` | Production tenant data |
| **Test** | `scp_test_` | `api.sandbox.sapphital.com` | Isolated sandbox data |

Tokens are mode-locked. A test token against production returns `401 authentication_error`.

## 5. Admin API — Resource Catalog

### 5.1 Commerce

| Resource | Path | Methods | Scope |
|----------|------|---------|-------|
| Products | `/products` | GET, POST, PATCH, DELETE | `read_products`, `write_products` |
| Variants | `/products/{id}/variants` | GET, POST, PATCH, DELETE | `write_products` |
| Collections | `/collections` | GET, POST, PATCH, DELETE | `write_products` |
| Inventory levels | `/inventory-levels` | GET, PATCH | `read_inventory`, `write_inventory` |
| Orders | `/orders` | GET, POST, PATCH | `read_orders`, `write_orders` |
| Fulfillments | `/orders/{id}/fulfillments` | GET, POST | `write_fulfillments` |
| Refunds | `/orders/{id}/refunds` | POST | `write_orders` |
| Draft orders | `/draft-orders` | GET, POST, PATCH, DELETE | `write_orders` |
| Discounts | `/discounts` | GET, POST, PATCH, DELETE | `write_discounts` |
| Gift cards | `/gift-cards` | GET, POST, PATCH | `write_discounts` |

### 5.2 Customers & CRM

| Resource | Path | Methods | Scope |
|----------|------|---------|-------|
| Customers | `/customers` | GET, POST, PATCH, DELETE | `read_customers`, `write_customers` |
| Customer addresses | `/customers/{id}/addresses` | GET, POST, PATCH, DELETE | `write_customers` |
| Customer segments | `/segments` | GET, POST, PATCH, DELETE | `read_customers` |
| Abandoned checkouts | `/abandoned-checkouts` | GET | `read_orders` |

### 5.3 Content & Storefront Config

| Resource | Path | Methods | Scope |
|----------|------|---------|-------|
| Pages | `/pages` | GET, POST, PATCH, DELETE | `write_content` |
| Blogs | `/blogs` | GET, POST, PATCH, DELETE | `write_content` |
| Navigation menus | `/menus` | GET, POST, PATCH, DELETE | `write_content` |
| Redirects | `/redirects` | GET, POST, DELETE | `write_content` |
| Themes | `/themes` | GET, POST | `write_themes` |
| Theme assets | `/themes/{id}/assets` | GET, PUT, DELETE | `write_themes` |

### 5.4 Marketplace (Multi-Vendor)

| Resource | Path | Methods | Scope |
|----------|------|---------|-------|
| Vendors | `/vendors` | GET, POST, PATCH | `read_vendors`, `write_vendors` |
| Vendor payouts | `/vendor-payouts` | GET, POST | `read_payouts` |
| Commissions | `/commissions` | GET | `read_orders` |

### 5.5 Developer & Platform

| Resource | Path | Methods | Scope |
|----------|------|---------|-------|
| Webhook endpoints | `/webhook-endpoints` | GET, POST, PATCH, DELETE | `write_webhooks` |
| Webhook deliveries | `/webhook-deliveries` | GET | `read_webhooks` |
| API tokens | `/api-tokens` | GET, POST, DELETE | `write_tokens` (merchant admin only) |
| Apps | `/apps` | GET, POST, DELETE | `write_apps` |
| Metafields | `/metafields` | GET, POST, PATCH, DELETE | Per-resource scope |
| Events (replay) | `/events` | GET | `read_webhooks` |

### 5.6 Reference Data

| Resource | Path | Methods | Scope |
|----------|------|---------|-------|
| Countries | `/reference/countries` | GET | Public (no auth) |
| Nigeria states & LGAs | `/reference/ng/states` | GET | Public |
| Currencies | `/reference/currencies` | GET | Public |
| Shipping zones | `/shipping-zones` | GET, POST, PATCH | `write_shipping` |

## 6. Storefront API — Resource Catalog

| Resource | Path | Methods | Auth |
|----------|------|---------|------|
| Products (public) | `/products` | GET | None |
| Collections | `/collections` | GET | None |
| Cart | `/cart` | GET, POST, PATCH, DELETE | Session cookie or `Cart-Token` header |
| Checkout | `/checkout` | POST, PATCH | Session |
| Customer account | `/account` | GET, PATCH | Customer token |
| Customer orders | `/account/orders` | GET | Customer token |
| Search | `/search` | GET | None |
| Store config | `/store` | GET | None |

**Rate limits:** Storefront API uses per-IP limits (300 req/min) plus per-store limits (1,000 req/min). See Chapter 11.

## 7. Representative OpenAPI Excerpts

### 7.1 Product Schema

```yaml
components:
  schemas:
    Product:
      type: object
      required: [id, object, name, status, created_at]
      properties:
        id:
          type: string
          pattern: '^prod_[a-zA-Z0-9]{8,}$'
          example: prod_8x9k2mNp
        object:
          type: string
          const: product
        name:
          type: string
          maxLength: 255
          example: Ankara Print Dress
        description:
          type: string
          nullable: true
        status:
          type: string
          enum: [DRAFT, ACTIVE, ARCHIVED]
        price:
          $ref: '#/components/schemas/Money'
        compare_at_price:
          $ref: '#/components/schemas/Money'
          nullable: true
        sku:
          type: string
          nullable: true
        inventory_quantity:
          type: integer
          minimum: 0
        images:
          type: array
          items:
            $ref: '#/components/schemas/ProductImage'
        variants:
          type: array
          items:
            $ref: '#/components/schemas/Variant'
          description: Included when expand[]=variants
        metafields:
          type: array
          items:
            $ref: '#/components/schemas/Metafield'
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time
```

### 7.2 Create Product

```yaml
paths:
  /products:
    post:
      operationId: createProduct
      summary: Create a product
      tags: [Products]
      security:
        - BearerAuth: [write_products]
      parameters:
        - $ref: '#/components/parameters/IdempotencyKey'
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [name, price]
              properties:
                name:
                  type: string
                  example: Ankara Print Dress
                price:
                  $ref: '#/components/schemas/Money'
                status:
                  type: string
                  enum: [DRAFT, ACTIVE]
                  default: DRAFT
            examples:
              nigeria_product:
                summary: Nigeria NGN product
                value:
                  name: Ankara Print Dress
                  price: { amount: 2500000, currency: NGN }
                  status: ACTIVE
      responses:
        '201':
          description: Product created
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Product'
        '400':
          $ref: '#/components/responses/BadRequest'
        '401':
          $ref: '#/components/responses/Unauthorized'
        '403':
          $ref: '#/components/responses/Forbidden'
        '429':
          $ref: '#/components/responses/RateLimited'
```

### 7.3 List Orders with Filters

```yaml
paths:
  /orders:
    get:
      operationId: listOrders
      summary: List orders
      tags: [Orders]
      security:
        - BearerAuth: [read_orders]
      parameters:
        - name: status
          in: query
          schema:
            type: string
            enum: [PENDING, PAID, FULFILLED, CANCELLED, REFUNDED]
        - name: created_at[gte]
          in: query
          schema:
            type: string
            format: date-time
        - name: payment_provider
          in: query
          schema:
            type: string
            enum: [PAYSTACK, FLUTTERWAVE, BANK_TRANSFER, COD]
          description: Filter by Nigeria/Kenya payment rail
        - $ref: '#/components/parameters/Limit'
        - $ref: '#/components/parameters/Cursor'
      responses:
        '200':
          description: Order list
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/OrderList'
```

### 7.4 Order Object (Payment Fields)

```yaml
    Order:
      type: object
      properties:
        id:
          type: string
          example: ord_7kL2mN9p
        object:
          const: order
        order_number:
          type: integer
          example: 1042
        status:
          type: string
          enum: [PENDING, PAID, FULFILLED, CANCELLED, REFUNDED]
        currency:
          type: string
          example: NGN
        subtotal:
          $ref: '#/components/schemas/Money'
        tax_total:
          $ref: '#/components/schemas/Money'
        shipping_total:
          $ref: '#/components/schemas/Money'
        total:
          $ref: '#/components/schemas/Money'
        payment:
          type: object
          properties:
            provider:
              type: string
              enum: [PAYSTACK, FLUTTERWAVE, BANK_TRANSFER, COD, MPESA]
            reference:
              type: string
              description: PSP transaction reference
            paid_at:
              type: string
              format: date-time
              nullable: true
        customer:
          $ref: '#/components/schemas/Customer'
        line_items:
          type: array
          items:
            $ref: '#/components/schemas/LineItem'
        shipping_address:
          $ref: '#/components/schemas/Address'
```

## 8. Expandable Fields

```http
GET /v1/orders/ord_7kL2mN9p?expand[]=customer&expand[]=line_items.product
```

| Resource | Expandable |
|----------|------------|
| Order | `customer`, `line_items`, `line_items.product`, `fulfillments` |
| Product | `variants`, `images`, `collections` |
| Customer | `addresses`, `orders` |

## 9. Metafields

Custom data on any resource (Shopify-compatible pattern):

```http
POST /v1/metafields
{
  "namespace": "erp_sync",
  "key": "sap_document_id",
  "value": "4500123456",
  "type": "single_line_text",
  "owner_resource": "order",
  "owner_id": "ord_7kL2mN9p"
}
```

| Type | Description |
|------|-------------|
| `single_line_text` | String ≤ 5000 chars |
| `number_integer` | Integer |
| `number_decimal` | Decimal string |
| `json` | JSON object |
| `boolean` | true/false |
| `url` | Valid HTTPS URL |

## 10. Webhook Topic Registration via API

```http
POST /v1/webhook-endpoints
{
  "url": "https://integrations.example.ng/scp/webhooks",
  "topics": ["order.paid", "order.fulfilled", "product.updated"],
  "description": "ERP sync — Lagos warehouse"
}
```

Response includes `secret` (shown once) for HMAC verification. See Chapter 04.

## 11. Tenant Isolation Rules

1. Token is bound to exactly one `tenant_id` at creation.
2. All queries inject `tenant_id` via middleware + Eloquent scope + RLS (ADR-002, ADR-005).
3. Resource IDs are unique per tenant; globally opaque.
4. `expand[]` cannot cross tenant boundaries.
5. Metafields inherit owner resource tenant scope.

## 12. Background Jobs Triggered by API

| API Action | Job | Webhook Topic |
|------------|-----|---------------|
| POST `/orders/{id}/fulfillments` | `DispatchFulfillmentNotifications` | `fulfillment.created` |
| POST `/orders/{id}/refunds` | `ProcessRefund` | `refund.created` |
| PATCH `/products/{id}` (status→ACTIVE) | `ReindexProduct` | `product.updated` |
| POST `/vendor-payouts` | `InitiateVendorPayout` | `payout.created` |

## 13. Performance Notes

| Endpoint Class | Cache | Target p95 |
|----------------|-------|------------|
| GET `/products` (list) | Redis 60s | 150ms |
| GET `/products/{id}` | Redis 120s | 100ms |
| GET `/orders` (list) | None | 200ms |
| POST `/orders` | None | 400ms |
| Storefront GET `/products` | CDN 300s | 80ms edge |

## 14. Acceptance Criteria

| ID | Criterion | Verification |
|----|-----------|--------------|
| AC-DEV-03-01 | All Admin API resources in §5 implemented in sandbox | Resource checklist test |
| AC-DEV-03-02 | OpenAPI spec generates valid PHP DTOs | CI codegen |
| AC-DEV-03-03 | Storefront cart/checkout flow completable via API | E2E headless test |
| AC-DEV-03-04 | Nigeria LGA reference endpoint returns 774 LGAs | Data validation |
| AC-DEV-03-05 | Metafields CRUD on products and orders | Integration test |
| AC-DEV-03-06 | Cross-tenant order access returns 404 | Isolation suite |

## 15. References

- Volume 5 Commerce Engine (domain model)
- Chapter 04 (webhook topics)
- Chapter 05 (scopes)
- Paystack API (reference for payment fields): https://paystack.com/docs/api/
