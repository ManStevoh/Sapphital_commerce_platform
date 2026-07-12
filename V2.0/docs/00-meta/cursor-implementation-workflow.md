# Cursor Implementation Workflow

**Document ID:** SCP-META-CUR-001  
**Version:** 1.0.0  
**Status:** ✅ Active  

---

## Purpose

How to use Cursor on SCP so **documentation drives code**, not the reverse. Copy-paste these patterns into every session.

---

## 1. Golden Rules

1. **Spec before code** — No implementation without document IDs cited
2. **Read list first** — Cursor must read listed files before editing
3. **Stop on conflict** — If code cannot match docs, explain and wait
4. **One concern per session** — Database OR backend OR frontend, not all
5. **Local module docs** — Package `docs/` + volume chapters together
6. **No architecture invention** — ADR required for structural changes

---

## 2. Standard Session Opener

Paste at the start of every implementation task:

```markdown
## SCP Implementation Task

**Role:** You are an implementation engineer. The specification is source of truth.
You are NOT the architect. Do not invent patterns not in the docs.

**Task ID:** [e.g. SCP-TASK-2026-0042]
**Goal:** [one sentence]

### Read first (mandatory — do not write code until read)
- [ ] V2.0/docs/00-meta/engineering-standards.md
- [ ] V2.0/docs/03-architecture/13-platform-os-architecture.md
- [ ] [volume chapter path]
- [ ] [Modules/Package/docs/ARCHITECTURE.md if exists]

### Implement exactly
- [specific acceptance criteria from volume]

### Do NOT
- Add dependencies not in module.json requires
- Import across Platform OS boundaries (see engineering-standards §5)
- Skip tests listed in spec

### If conflict
Stop. List: (1) doc citation, (2) conflict, (3) proposed ADR or doc fix.
Do not guess.
```

---

## 3. Session Types

### Architecture / Interfaces only

```markdown
Read: ADR-XXX, module ARCHITECTURE.md, Platform OS Ch. 13.
Output: PHP interfaces, DTOs, event classes ONLY. No migrations, no UI.
```

### Database session

```markdown
Read: module DATABASE.md, Vol 17 Ch. 02–05, ADR-002.
Output: migrations + RLS policies + factories. No controllers.
```

### Backend session

```markdown
Read: module API.md, Vol 5 relevant chapter, engineering-standards.md.
Output: Actions, repositories, controllers, policies, Pest tests.
```

### Frontend session

```markdown
Read: Vol 4 UI chapter, module UI.md, design system components.
Output: React components with loading/empty/error states. API client only.
```

### Connector session

```markdown
Read: Vol 5 Ch. 17, Packages/contracts/, Connectors/{Name}/docs/.
Output: Adapter class implementing contract + integration tests with mocked HTTP.
```

### Test session

```markdown
Read: module TESTING.md, Vol 13, existing feature tests.
Output: Tests only. Fix implementation if tests reveal spec violations.
```

---

## 4. Example: Product CRUD Task

```markdown
**Task ID:** SCP-TASK-2026-0101
**Goal:** Implement Product aggregate CRUD per Vol 5 Ch. 01.

### Read first
- V2.0/docs/05-commerce-engine/01-product-catalog.md
- V2.0/docs/00-meta/engineering-standards.md
- V2.0/docs/03-architecture/03-bounded-contexts-and-modules.md
- Modules/Commerce/docs/ARCHITECTURE.md (create from template if missing)

### Acceptance criteria
- [ ] Product + Variant aggregates with FR-020 tenant_id
- [ ] Money value object for prices (FR-021)
- [ ] ProductCreated event (FR-022)
- [ ] Policy: commerce.products.*
- [ ] Pest: CRUD + tenant isolation + 403
- [ ] API.md updated with endpoints

### Out of scope
- Images (separate task)
- Search indexing (listener task)
- Admin UI (frontend session)
```

---

## 5. Cursor Project Rules (Auto-Loaded)

These live in `.cursor/rules/`:

| Rule | Scope |
|------|-------|
| `doc-first-implementation.mdc` | Always — read docs before code |
| `platform-os-boundaries.mdc` | `Platform/**`, `Modules/**`, `Connectors/**` |
| `scp-full-documentation.mdc` | When editing `V2.0/docs/**` |
| `.cursor/rules/testing-and-quality.mdc` | Always — tests required, fix failures, no broken UI/500s |
| `.cursor/rules/no-npm-build.mdc` | Always — no build/install unless user asks |

---

## 6. After Implementation

Cursor (or engineer) verifies:

- [ ] Acceptance criteria met
- [ ] `CHANGELOG.md` if package version bump
- [ ] Module `API.md` / `EVENTS.md` / `DATABASE.md` synced
- [ ] No TODO in payment/auth paths without issue link
- [ ] PR description cites task ID and read list

---

## 7. Anti-Patterns

| Bad prompt | Why |
|------------|-----|
| "Build ecommerce platform" | No spec anchor |
| "Add payments" | Which adapter? Which ADR? |
| "Fix this bug" without spec | May violate architecture |
| "Refactor everything" | Unbounded scope |
| One prompt for full module | Context overload, drift |

---

## References

- [Engineering Knowledge Base](./engineering-knowledge-base.md)
- [Task Specification Template](./task-specification-template.md)
- [Module Template](./module-template.md)
