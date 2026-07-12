# Engineering Knowledge Base

**Document ID:** SCP-META-EKB-001  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Owner:** Stephen Musyoka Makola  
**Audience:** Engineers, Cursor agents, reviewers  

---

## Purpose

This is the **master guide** for building SCP without architecture drift. The specification in `V2.0/docs/` is the **source of truth**. Cursor (and all engineers) are **implementation engineers**, not architects.

> Never ask: *"Build an eCommerce platform."*  
> Always ask: *"Implement SCP-ARCH-001-13 §12 and Vol 5 Ch. 01 exactly as documented."*

---

## 1. Philosophy

| Role | Responsibility | Must not |
|------|----------------|----------|
| **Lead Architect (Stephen)** | ADRs, Platform OS, volume structure, acceptance criteria | Write production code without spec |
| **Specification (docs/)** | What to build, boundaries, contracts, tests | Be ignored during implementation |
| **Cursor / Engineers** | Implement approved specs, raise conflicts | Invent architecture not in docs |
| **Architect AI** (future) | Validate code vs standards, boundary violations | Replace human architecture review |

### The workflow (every feature)

```text
Architecture (docs + ADR)
    ↓ Review / approve
Task specification (task template)
    ↓
Cursor reads local module docs + volume chapters
    ↓
Implementation + tests
    ↓
Review (human + checklist)
    ↓
Documentation + CHANGELOG updated
    ↓
Merge
```

**Never skip:** spec → task → read docs → code.

---

## 2. Repository Layout (Normative)

Implementation repository follows [Platform OS Architecture](../03-architecture/13-platform-os-architecture.md) (ADR-023):

```text
sapphital-commerce/
├── V2.0/docs/              # Source of truth (this knowledge base)
├── apps/                   # Layer 0 — client runtimes (ADR-017)
│   ├── admin/
│   ├── storefront/
│   ├── visual-builder/
│   └── …
├── Platform/               # Kernel + platform services
├── Modules/                # Business products + extensions
├── Connectors/             # External adapters
├── Themes/                 # Theme packages
├── AI/                     # AI skill packages
├── Packages/               # Shared libs (Money, contracts, theme-sdk)
├── app/                    # Thin Laravel shell
├── tests/
├── .cursor/rules/          # Cursor project rules
└── .github/                # PR templates, CI
```

**Docs are first-class.** `V2.0/docs/` is not an afterthought — it governs `Platform/`, `Modules/`, and `apps/`.

### Mapping Stephen's doc taxonomy → SCP volumes

| Concept | SCP location |
|---------|--------------|
| Vision | `01-vision/` |
| Principles | `00-meta/engineering-principles.md` |
| Architecture | `03-architecture/` (+ ADR-023 Platform OS) |
| Platform / Kernel | `03-architecture/13-platform-os-architecture.md`, `16-saas-multi-tenancy/` |
| Modules / Commerce | `05-commerce-engine/`, `Modules/*/docs/` (implementation) |
| UI | `04-design-system/` |
| AI | `09-ai-platform/`, `00-meta/prompts/` |
| Database | `17-database-data-architecture/` |
| API | `12-developer-platform/`, `03-architecture/08-*` |
| Security | `11-security/` |
| Testing | `13-testing/` |
| Deployment | `10-infrastructure/`, `14-operations/` |
| ADRs | `00-meta/adr/` |
| Build order | `21-implementation-playbooks/` → **[Ch. 00 Master Execution Plan](../21-implementation-playbooks/00-master-execution-plan.md)** |

---

## 3. Knowledge Base Index

Read these before writing code:

| Document | When to read |
|----------|--------------|
| [Engineering Standards](./engineering-standards.md) | Every PR |
| [Module Template](./module-template.md) | New package under Platform/Modules/Connectors/AI |
| [Implementation Knowledge Graph](./implementation-knowledge-graph.md) | Understanding dependencies |
| [Cursor Implementation Workflow](./cursor-implementation-workflow.md) | Every Cursor session |
| [Task Specification Template](./task-specification-template.md) | Every feature task |
| [Architect AI Governance](./architect-ai-governance.md) | Automated review design |
| [Engineering Principles](./engineering-principles.md) | Onboarding |
| [Platform OS Ch. 13](../03-architecture/13-platform-os-architecture.md) | Any cross-package work |
| [Implementation Playbooks Vol 21](../21-implementation-playbooks/README.md) | Sprint planning — **start at Ch. 00 Master Execution Plan** |

---

## 4. Module Documentation Contract

Every installable package (ADR-023) ships **local docs** under its own `docs/` folder:

```text
Modules/Commerce/docs/
├── README.md           # Overview, install, dependencies
├── ARCHITECTURE.md     # Internal design, bounded contexts
├── API.md              # Public HTTP/events consumed & published
├── DATABASE.md         # Tables, indexes, RLS (or link to Vol 17)
├── EVENTS.md           # Domain events matrix
├── PERMISSIONS.md      # Roles and gates
├── CONFIG.md           # Env keys, feature flags
├── UI.md               # Admin screens (links to Vol 4)
├── WORKFLOW.md         # State machines, sagas
├── TESTING.md          # How to run tests, coverage targets
├── UPGRADE.md          # Breaking changes, migrations
├── CHANGELOG.md        # Semver history
└── TODO.md             # Spec gaps only (with issue links)
```

**Cursor rule:** Before editing `Modules/Commerce/src/**`, read `Modules/Commerce/docs/ARCHITECTURE.md` and relevant volume chapters.

Volume chapters remain the **canonical deep spec** until module-local docs are generated during scaffold. Module docs **summarize and link** — they do not contradict volumes.

---

## 5. Task-Based Development

No broad prompts. Every task uses [Task Specification Template](./task-specification-template.md):

1. **Spec references** — Document IDs (e.g. SCP-COM-005-01, ADR-019)
2. **Read list** — Exact files Cursor must read first
3. **Acceptance criteria** — Testable, from volume AC sections
4. **Out of scope** — Explicit boundaries
5. **Conflict protocol** — Stop and explain if code cannot match docs

Example task opener for Cursor:

```text
Implement Product CRUD per task SCP-TASK-2026-0142.

Read first:
- V2.0/docs/05-commerce-engine/01-product-catalog.md
- Modules/Commerce/docs/ARCHITECTURE.md
- V2.0/docs/00-meta/engineering-standards.md

Do not introduce architecture not described.
If implementation conflicts with documentation, stop and explain why.
```

---

## 6. Multi-Agent / Multi-Session Pattern

Split Cursor work by **single responsibility**. Each session reads specific docs only:

| Session | Reads | Produces |
|---------|-------|----------|
| Architecture | ADR + Platform OS + module ARCHITECTURE.md | Interfaces, contracts only |
| Database | Vol 17 + module DATABASE.md | Migrations, RLS policies |
| Backend | module API.md + Vol 5/12 | Actions, services, controllers |
| Frontend | Vol 4 UI chapters + module UI.md | React components |
| Connectors | Vol 5 Ch. 17 + connector README | Adapter implementing contract |
| Testing | Vol 13 + module TESTING.md | Pest/Playwright tests |
| Documentation | All of the above | Verify API/events/UPGRADE synced |

Do not combine "build entire Commerce module" in one session.

---

## 7. Sprint Sequence (Platform First)

Per [Vol 21 Ch. 02](../21-implementation-playbooks/02-phase1-foundation-playbook.md), build **platform before products**:

| Sprint block | Package | Spec |
|--------------|---------|------|
| 1–2 | Repository + CI | Vol 21 Ch. 02 §1 |
| 3 | Identity | Vol 3 Ch. 06, ADR-006 |
| 4 | Tenancy + RLS | Vol 3 Ch. 05, ADR-002 |
| 5 | Billing + entitlements | Vol 16 Ch. 03–04 |
| 6 | Provisioning (TPE) | Vol 16 Ch. 10, ADR-022 |
| 6b | Platform Admin + marketing signup | Vol 16 Ch. 11–12, ADR-023 |
| 7+ | Commerce slices | Vol 5, Vol 21 Ch. 03 |

Each sprint exit = acceptance criteria from playbook + volume AC sections.

---

## 8. GitHub / PR Governance

Every PR must satisfy [Engineering Standards Checklist](../21-implementation-playbooks/09-engineering-standards-checklist.md):

- [ ] Linked task spec or FR/NFR/ADR ID
- [ ] Spec read list acknowledged in PR description
- [ ] Tests added/updated
- [ ] Module `CHANGELOG.md` updated if package semver bump
- [ ] `docs/` updated if **behavior** or **contract** changed
- [ ] New ADR if architecture changed (no silent drift)
- [ ] OpenAPI updated if public API changed

Use `.github/PULL_REQUEST_TEMPLATE.md` (implementation repo).

---

## 9. Prompt Library

AI prompts live in **docs**, not embedded in application code:

```text
V2.0/docs/00-meta/prompts/
├── catalog-agent.md
├── marketing-agent.md
├── support-agent.md
├── onboarding-agent.md
├── theme-agent.md
└── developer-agent.md
```

See [Vol 9 Ch. 21](../09-ai-platform/21-ai-observability-prompts-security-learning.md). Application code references prompt **version IDs**, not raw prompt text.

---

## 10. Preventing Documentation Drift

| Mechanism | Action |
|-----------|--------|
| Cursor rules | `.cursor/rules/doc-first-implementation.mdc`, `performance-security-scalability.mdc`, `testing-and-quality.mdc`, `no-npm-build.mdc`, `platform-os-boundaries.mdc` |
| Boundary lint | CI: no `Modules\Commerce` imports in `Platform/` |
| PR template | Requires spec citation |
| Architect AI | Phase 2: automated boundary + doc completeness checks |
| Quarterly audit | Compare `Platform/` tree to Ch. 13 inventory |

When code **must** diverge from docs: update docs **in the same PR** or file ADR **before** merging code.

---

## 11. Acceptance Criteria (Knowledge Base)

- [ ] Every engineer and Cursor session follows doc-first workflow
- [ ] Module template used for all new packages
- [ ] Task template used for all features
- [ ] PR template enforces doc/test/changelog/ADR gates
- [ ] Knowledge graph kept current when packages added
- [ ] Prompt library externalized from code

---

## References

- [ADR-023 — Platform OS](./adr/023-sapphital-platform-os.md)
- [Legacy Capability Matrix](./legacy-capability-matrix.md)
- [Document Control](./document-control.md)
- [Cursor Implementation Workflow](./cursor-implementation-workflow.md)
