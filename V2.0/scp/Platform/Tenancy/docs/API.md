# Tenancy — API

**Package:** `Platform/Tenancy`  
**Base path:** `/api/v1/platform/tenancy`  
**Document ID:** SCP-PLAT-TEN-API-001

---

### GET /api/v1/platform/tenancy/health

**Permission:** none (public health probe)

**Request:** none

**Response:** 200

```json
{
  "status": "ok",
  "package": "tenancy"
}
```

**Events:** none

**Errors:** none expected in normal operation

**Tests:** `tests/Feature/HealthTest.php`

**Route name:** `tenancy.health.show`

---

### GET /api/v1/platform/tenancy/tenants/by-slug/{slug}

**Permission:** none (public tenant lookup for storefront edge resolution)

**Response:** 200

```json
{
  "id": "uuid",
  "slug": "lagos-tech",
  "name": "Lagos Tech Shop",
  "status": "active"
}
```

**Errors:** 404 unknown slug

**Tests:** `tests/Feature/Tenancy/TenantBySlugTest.php`

**Route name:** `tenancy.tenants.show-by-slug`

---

### GET /api/v1/platform/tenants

**Permission:** `auth:sanctum` — platform admin only (`PlatformAdmin` instance)

**Query params:** `per_page` (optional, default 15, max 50)

**Response:** 200

```json
{
  "data": [
    {
      "id": "uuid",
      "slug": "lagos-tech",
      "name": "Lagos Tech Shop",
      "status": "active",
      "country": "NG",
      "created_at": "2026-07-12T10:00:00+00:00"
    }
  ],
  "meta": {
    "total": 1
  }
}
```

**Errors:** 401 unauthenticated · 403 non-platform admin

**Tests:** `tests/Feature/PlatformAdmin/TenantListTest.php`

**Route name:** `tenancy.platform.tenants.index`
