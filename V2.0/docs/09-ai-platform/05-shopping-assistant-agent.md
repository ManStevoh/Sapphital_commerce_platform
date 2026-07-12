# Chapter 05: Shopping Assistant Agent

**Document ID:** SCP-AI-001-05  
**Version:** 1.0.0  
**Status:** 📝 Draft  
**Traceability:** FR-AI-003, FR-AI-009, FR-AI-010, NFR-001, NFR-047, NFR-079  

---

## 1. Purpose

Define the **customer-facing shopping assistant** embedded in storefronts: natural-language product discovery, comparison, cart assistance, order tracking, and policy questions — optimized for Nigerian mobile shoppers using English or Pidgin (Hausa/Yoruba/Igbo in Phase 1.5).

## 2. Scope

- User journeys and UX placement
- Agent persona and prompt policy
- Tool subset and business rules
- RAG grounding requirements
- Localization
- Analytics and conversion attribution

## 3. Out of Scope

- Autonomous checkout payment (customer always confirms pay)
- WhatsApp channel (Phase 2 messaging integrations)
- Bargaining / dynamic pricing negotiation

## 4. User Journeys

### Journey A: Product discovery (mobile)

1. Customer opens the embedded product finder or floating assistant (non-intrusive per Product Principle 4)
2. "Abeg I need ankara dress for owambe, budget na 25k"
3. Agent calls `search_products` + RAG on collections
4. Shows 3–5 products with images (carousel UI component)
5. Customer taps "Add the blue one" → `add_to_cart` with confirm chip

### Journey B: Order tracking

1. "Where my order dey? Order number SCP-9281"
2. Agent calls `get_order_status` (requires logged-in session or order email verification)
3. Returns fulfillment timeline in plain language

### Journey C: Policy question

1. "Wetin be your return policy for shoes?"
2. RAG retrieves merchant policy chunk; answer cites policy page link

## 5. UX Surfaces

| Element | Requirement |
|---------|-------------|
| Entry | Embedded product-finder section, search recovery, PDP questions, or floating action button |
| Placement | Embedded in page flow where discovery is primary; launcher bottom-right without covering fixed UI |
| Streaming | Token-by-token with skeleton first message |
| Product cards | Native theme component; LCP impact ≤ 100 ms incremental |
| Citations | "Source: Return Policy" links |
| Language toggle | EN / PCM / (HA/YO/IG Phase 1.5) in chat header |
| Accessibility | WCAG 2.2 AA; `aria-live="polite"` for new messages |

### 5.1 Embedded Product Finder

Homepage and collection templates may render `ai-product-finder` with a visible intent input and contextual prompt chips. This is the preferred discovery treatment; the floating launcher is a secondary support entry.

Example Nigeria prompts:

- “Phone under ₦250,000”
- “Birthday gift for my wife”
- “School shoes, size 36”
- “Coffee machine for a small office”

No-results search may offer the assistant with the failed query prefilled. PDP mode is grounded to the current product and merchant policies.

### 5.2 Fixed-Layer Collision

The launcher hides during checkout and shifts above bottom navigation or sticky purchase controls. It must never obscure consent controls, cart actions, or Add to Cart. Volume 6 Chapter 11 defines priority.

## 6. Agent Configuration

```yaml
agent_type: shopping_assistant
default_model_preference: fast
temperature: 0.4
max_tool_steps: 3
tools:
  - search_products
  - get_product_details
  - add_to_cart
  - get_order_status
  - get_shipping_quote
rag:
  source_types: [product, collection, policy, faq, shipping_rule]
  min_score: 0.74
memory:
  read: customer long-term facts
  write: with explicit confirm
```

## 7. Prompt Policy (Summary)

**System prompt pillars:**

1. You represent `{store_name}` only; never mention competitors
2. Ground product claims in tool results or RAG citations
3. If unsure, say so — do not invent stock or prices
4. Match user language register (English, Pidgin, or local language)
5. Never collect card numbers or passwords in chat
6. Escalate to human support on abuse, self-harm, or legal threats

**Pidgin example tone:** Friendly, concise, avoids forced slang; respects customer switching to English.

## 8. Business Rules

| Rule | Detail |
|------|--------|
| BR-SA-01 | Prices shown must match Commerce API at invoke time |
| BR-SA-02 | Out-of-stock items may be suggested with notify-me link only |
| BR-SA-03 | `add_to_cart` max 10 units per SKU per turn |
| BR-SA-04 | Guest users: order tracking requires email + order ID match |
| BR-SA-05 | Marketplace stores: show vendor name on multi-vendor results |
| BR-SA-06 | Promotional claims must reference active `promotion` entity |
| BR-SA-07 | Medical/health product claims blocked (safety category) |

## 9. Authorization

| Action | Auth |
|--------|------|
| Browse/search | Public (store context) |
| Add to cart | Customer session or guest cart token |
| Order status | Customer session OR verified order lookup |
| Memory write | Authenticated customer only |

## 10. Events & Analytics

| Event | Use |
|-------|-----|
| `ShoppingAssistantOpened` | Funnel |
| `ShoppingAssistantProductClicked` | Attribution |
| `ShoppingAssistantAddToCart` | Conversion |
| `ShoppingAssistantEscalated` | Support load |

**Success metric:** Assisted conversion rate ≥ 8% of engaged sessions (Phase 1 target, validate per vertical).

## 11. Performance

- Widget lazy-loaded after first interaction or 5s idle (NFR-009 budget)
- First token p95 ≤ 800 ms on 4G throttled profile
- Product card payload from existing product API cache

## 12. Security & Safety

- Rate limit: 30 messages/hour per IP guest; 60 authenticated
- Prompt injection via product titles handled in RAG tags (Ch. 09)
- No PII in URLs returned to model beyond order last-4 digits

## 13. Localization Matrix

| Locale | Code | Phase | Notes |
|--------|------|-------|-------|
| English (Nigeria) | `en-NG` | 1 | Default |
| Nigerian Pidgin | `pcm-NG` | 1 | Full system prompt variant |
| Hausa | `ha` | 1.5 | + translated UI strings |
| Yoruba | `yo` | 1.5 | |
| Igbo | `ig` | 1.5 | |

Auto-detect from first message; persist in `ai_conversations.locale`.

## 14. Merchant Controls

Settings → Store → AI Assistant:

- Enable/disable
- Welcome message override
- Block categories from AI suggestions
- Custom FAQ priority list

## 15. Test Strategy

- Journey E2E on 320px viewport
- Pidgin golden utterances (50)
- Price accuracy test: change price mid-chat → agent reflects update on next tool call
- a11y: NVDA read order for streamed messages

## 16. Acceptance Criteria

- [ ] Chat widget passes WCAG 2.2 AA audit
- [ ] Pidgin mode available at launch
- [ ] 100% price/stock answers sourced from tools/RAG
- [ ] Add-to-cart requires visible user confirm tap
- [ ] Escalation path to human support works
- [ ] Embedded product finder works on homepage and no-results search
- [ ] Launcher passes 320px fixed-layer collision tests
- [ ] Prompt examples display tenant-localized currency rather than hard-coded KES/NGN

## 17. Sources

- Volume 1 Product Principle 4 (AI invisible until needed)
- Volume 1 Target Markets — Nigeria languages
- Mobile commerce UX patterns (E3)
