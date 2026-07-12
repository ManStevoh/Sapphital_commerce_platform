# Identity — API

**Package:** `Platform/Identity`  
**Base path:** `/api/v1` (auth) · `/api/v1/platform/identity` (health)  
**Document ID:** SCP-PLAT-ID-API-001

---

### POST /api/v1/auth/merchant/login

**Permission:** none (public; rate-limited `5/min`)

**Request:**

```json
{
  "email": "owner@merchant.test",
  "password": "secret"
}
```

**Response:** 200

```json
{
  "token": "<sanctum-plain-text-token>",
  "token_type": "Bearer"
}
```

**Errors:** 401 `{"message":"Invalid credentials."}` (uniform — no account enumeration)

**Route name:** `identity.auth.merchant.login`

---

### POST /api/v1/auth/platform/login

**Permission:** none (public; rate-limited `5/min`)

**Request:** same shape as merchant login

**Response:** 200 — same token envelope as merchant login

**Errors:** 401 `{"message":"Invalid credentials."}`

**Route name:** `identity.auth.platform.login`

---

### GET /api/v1/auth/me

**Permission:** authenticated principal (`auth:sanctum`)

**Request:** `Authorization: Bearer <token>`

**Response:** 200

```json
{
  "id": "<uuid>",
  "type": "merchant",
  "email": "owner@merchant.test",
  "tenant_id": "<uuid>"
}
```

**Errors:** 401 when unauthenticated

**Route name:** `identity.auth.me`

---

### GET /api/v1/platform/identity/health

**Permission:** none (public health probe)

**Request:** none

**Response:** 200

```json
{
  "status": "ok",
  "package": "identity"
}
```

**Events:** none

**Errors:** none expected in normal operation

**Route name:** `identity.health.show`
