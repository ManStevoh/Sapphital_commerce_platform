# Chapter 09: CLI — `scp` Tool

**Document ID:** SCP-DEV-001-09  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** PRD-009, NFR-071, ADR-003

---

## Purpose

Specify the **`scp` command-line interface** for theme developers, plugin authors, and agency partners building on SCP — optimized for Nigeria's mobile-first developer community and intermittent connectivity.

## Scope

- CLI installation and authentication
- Theme scaffolding, validate, deploy
- Plugin scaffolding and local testing
- Sandbox tenant management
- Webhook testing and log tailing
- CI/CD integration hooks

## Out of Scope

- Platform infrastructure CLI (Volume 10)
- Merchant admin tasks (web UI)
- GraphQL codegen (Phase 3)

---

## 1. Design Goals

| Goal | Implementation |
|------|----------------|
| Works on low bandwidth | Minimal dependencies; resume uploads |
| Familiar to Shopify developers | `scp theme push` parallels `shopify theme push` |
| Secure by default | OAuth device flow; tokens in OS keychain |
| CI-friendly | Non-interactive mode with API tokens |
| Cross-platform | Windows, macOS, Linux (Node.js binary) |

---

## 2. Installation

```bash
# npm global (preferred)
npm install -g @sapphital/scp-cli

# Standalone binary
curl -fsSL https://cli.sapphital.com/install.sh | sh

# Verify
scp version
# scp-cli 1.0.0 — API storefront/v2026-07
```

**Requirements:** Node.js 22+, HTTPS outbound to `api.sapphital.com`.

---

## 3. Authentication

```bash
# Interactive — device code flow (OAuth 2.1)
scp auth login
# Visit https://admin.sapphital.com/device and enter code: ABCD-1234

# CI — personal access token
export SCP_TOKEN=scp_pat_...
scp auth whoami

# Project-level — .scp/config.json (gitignored)
{
  "store": "merchant-slug",
  "theme_id": "uuid",
  "api_version": "2026-07"
}
```

Tokens scoped to `theme:write`, `plugin:write`, `webhook:read` per Volume 12 Ch. 05.

---

## 4. Theme Commands

| Command | Purpose |
|---------|---------|
| `scp theme init [name]` | Scaffold theme from Lagos starter |
| `scp theme dev` | Local preview with hot reload |
| `scp theme check` | Validate schema, bundle budget, CSP |
| `scp theme push` | Upload to draft theme |
| `scp theme publish` | Promote draft to live |
| `scp theme pull` | Download remote theme |
| `scp theme migrate` | Run schema migrations (Ch. 10) |

```bash
scp theme init my-store-theme --base=lagos
cd my-store-theme
scp theme dev --store=demo-lagos

# Pre-publish validation
scp theme check
# ✓ schema_version 2.0
# ✓ JS bundle 87 KB (limit 100 KB)
# ✓ no CSP violations in static analysis
# ✓ contrast pairs valid

scp theme push --draft
scp theme publish --confirm
```

---

## 5. Plugin Commands

| Command | Purpose |
|---------|---------|
| `scp app init [name]` | Scaffold plugin manifest + bootstrap |
| `scp app dev` | Local tunnel to sandbox tenant |
| `scp app validate` | Manifest, hooks, scope check |
| `scp app deploy` | Upload to tenant or marketplace staging |
| `scp app logs` | Tail plugin execution logs |

```bash
scp app init inventory-sync --template=webhook-handler
scp app dev --tunnel
# Webhook URL: https://tunnel.sapphital.dev/abc123
```

---

## 6. Webhook & API Utilities

```bash
# Listen for webhooks locally
scp webhooks listen --port=3000 --filter=orders.*

# Replay delivery
scp webhooks replay --delivery_id=uuid

# API smoke test
scp api get /admin/v1/products --limit=5
```

Offline mode: queues commands in `~/.scp/offline-queue.json`; syncs when connectivity returns.

---

## 7. Sandbox Tenants

| Command | Purpose |
|---------|---------|
| `scp sandbox create` | Provision 14-day dev store |
| `scp sandbox seed` | Load sample products (NGN) |
| `scp sandbox reset` | Wipe data, keep theme |

Free tier: 1 sandbox per developer account. Nigeria university program: 3 sandboxes.

---

## 8. CI/CD Integration

```yaml
# GitHub Actions example
- name: Validate theme
  run: scp theme check --ci
  env:
    SCP_TOKEN: ${{ secrets.SCP_THEME_TOKEN }}

- name: Deploy to staging store
  run: scp theme push --store=staging-demo --draft
```

`--ci` flag: non-zero exit on warnings configured as errors; JSON output for parsers.

---

## 9. Configuration Precedence

```text
1. CLI flags
2. Environment variables (SCP_TOKEN, SCP_STORE)
3. .scp/config.json in project root
4. ~/.scp/global.json
5. Built-in defaults (api.sapphital.com)
```

---

## 10. Error Handling

| Exit Code | Meaning |
|-----------|---------|
| 0 | Success |
| 1 | Validation failure |
| 2 | Auth failure |
| 3 | Network error (retry suggested) |
| 4 | Rate limited (respect Retry-After) |

Verbose: `scp --debug theme push` — logs request IDs for support.

---

## 11. Acceptance Criteria

- [ ] Install methods: npm global and install script
- [ ] OAuth device flow and PAT auth documented
- [ ] Theme commands: init, dev, check, push, publish, migrate
- [ ] Plugin commands: init, dev, validate, deploy
- [ ] Webhook listen and replay utilities
- [ ] Sandbox tenant lifecycle commands
- [ ] CI `--ci` flag with GitHub Actions example
- [ ] Offline queue for intermittent connectivity

---

## References

- [Volume 6 Ch. 06 — Theme SDK](./../06-theme-engine/06-theme-sdk-and-cli.md)
- [Chapter 07 — Plugin Runtime](./07-plugin-runtime.md)
- [Chapter 10 — App Review](./10-app-review-marketplace.md)
- [Chapter 05 — Authentication](./05-authentication-oauth-scopes.md)
