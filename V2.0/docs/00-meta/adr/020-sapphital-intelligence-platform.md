# ADR-020: SAPPHITAL Intelligence Platform (AI Operating System)

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 9 — AI Platform; Volume 1 — Vision

## Context

The wrong question is *"Which AI model should I use?"* The right question is *"How do I build an AI Operating System for Commerce?"*

SCP must not ship an AI chatbot bolted onto a website. AI must become the **intelligence layer** that powers commerce, marketplace, ERP, POS, CRM, Learning, and future SAPPHITAL products — built once, consumed everywhere.

## Decision

Establish **SAPPHITAL Intelligence Platform (SIP)** as a **platform independent of the Commerce Engine**, exposed through stable capability APIs:

| Capability | Description |
|------------|-------------|
| Conversation | Multi-turn dialogue with streaming |
| Reasoning | Planning, analysis, recommendations |
| Search | Semantic + hybrid retrieval |
| Memory | Session, merchant, long-term, timeline |
| Agent orchestration | Single and multi-agent workflows |
| Document understanding | PDF, invoice, policy ingestion |
| Image understanding | Product, receipt, barcode, shelf |
| Voice | STT, TTS, voice commerce |
| Code generation | Developer assistant, theme scaffolding |
| Workflow automation | Event-driven AI pipelines |

### Three-Platform Ecosystem

```text
1. SAPPHITAL Core Platform     — Identity, tenancy, billing, notifications, audit
2. SAPPHITAL Intelligence      — AI Gateway, router, memory, RAG, agents, workflows
3. SAPPHITAL Business Apps     — Commerce, Marketplace, ERP, CRM, POS, Learning
```

Business applications **consume** Intelligence capabilities via internal APIs. They do not embed provider SDKs or hardcode prompts.

### AI OS Kernel (Implementation Shape)

```text
                    AI Kernel
                        │
    ┌───────────────────┼───────────────────┐
    │                   │                   │
 Memory            Orchestrator        Knowledge
    │                   │                   │
    └───────────────────┼───────────────────┘
                        │
                  Capability Layer
    ┌──────────────────────────────────────────┐
    │ Search │ Vision │ Voice │ Reasoning │ RAG│
    └──────────────────────────────────────────┘
                        │
                 Business Agents / Skills
                        │
                 SAPPHITAL Products
```

### Seven Layers (Normative)

1. **AI Gateway** — No direct provider calls from application code
2. **AI Router** — Task-based model selection (writing, vision, fast chat, code, translation)
3. **AI Memory** — Short-term, session, merchant, long-term vector, global (LLM)
4. **Knowledge Engine** — RAG over merchant docs, policies, catalog
5. **AI Agents** — Specialized agents (catalog, marketing, inventory, finance, support, …)
6. **AI Workflow Engine** — Automated pipelines (upload product → full publish flow)
7. **Multi-Agent Collaboration** — Orchestrator coordinates specialists (pharmacy store setup)

### Non-Negotiables

- Model-agnostic gateway (OpenAI, Anthropic, Google, DeepSeek, Mistral, local Phase 3+)
- Prompt versioning as versioned assets (rollback like code)
- Security pipeline: permission → rate limit → prompt guard → PII filter → model → output validation
- AI observability: cost, latency, success rate, hallucination reports, satisfaction
- Human-in-the-loop for irreversible actions (publish, refund, price change)
- Explainability + internal confidence scores on recommendations

## Alternatives Considered

| Alternative | Why Rejected |
|-------------|--------------|
| Single model (OpenAI only) | Provider lock-in; task-optimal models differ |
| AI inside Commerce module only | Cannot reuse for ERP, Learning, POS |
| Chatbot widget only | Does not automate workflows or agents |
| Microservice Day 1 | ADR-001 modular monolith first; extract at 500 RPS |

## Consequences

### Positive

- One AI investment serves entire SAPPHITAL ecosystem
- Model swaps without application redeploys
- Skills marketplace and developer AI on same kernel
- Digital twin and business graph become platform assets

### Negative

- Higher upfront abstraction cost
- Prompt/version governance overhead
- Multi-agent orchestration complexity and cost controls

## References

- [Volume 9 Ch. 17 — AI OS Architecture](../../09-ai-platform/17-ai-operating-system-architecture.md)
- ADR-001, ADR-002, ADR-007, ADR-019, ADR-023
