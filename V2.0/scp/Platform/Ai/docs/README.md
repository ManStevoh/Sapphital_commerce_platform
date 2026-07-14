# Platform AI (SIP gateway v1)

**Package:** `Platform/Ai`  
**Traceability:** Vol 9 Ch. 02, Phase 2 playbook §3

## Capabilities

- Model gateway with primary/fallback providers (`fake` default, `openai`, `null`)
- Prompt templates (`ai_prompt_templates`) versioned by `feature_key`
- Tenant usage ledger (`ai_usage_events`) with prompt hash + watermark flag
- PII scrubbing (email / NG phone) before completion
- Daily request limits by plan (`starter` 50 / `growth` 200 / `pro` 500)
- Merchant opt-out via `tenants.settings.ai_enabled = false`

## API

| Method | Path | Auth |
|--------|------|------|
| POST | `/api/v1/platform/ai/product-description` | merchant + `catalog.write` |
| POST | `/api/v1/platform/ai/seo-meta` | merchant + `catalog.write` |
| POST | `/api/v1/platform/ai/collection-description` | merchant + `catalog.write` |
| POST | `/api/v1/platform/ai/support-reply` | merchant + `catalog.write` |
| POST | `/api/v1/platform/ai/zero-result-suggest` | merchant + `catalog.write` |
| GET | `/api/v1/platform/ai/usage` | merchant |
| PUT | `/api/v1/platform/ai/settings` | merchant (`ai_enabled`) |

All generators return an **editable draft** only — never auto-publish. Support reply and prompts scrub PII before completion.
