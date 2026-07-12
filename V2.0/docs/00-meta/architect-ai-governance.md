# Architect AI — Engineering Governance

**Document ID:** SCP-META-AAI-001  
**Version:** 1.0.0  
**Status:** 📝 Draft (design)  
**Traceability:** ADR-020, Vol 9, Engineering Knowledge Base  

---

## Purpose

Define an **internal Architect AI** — not a code generator, but a **technical lead agent** that validates implementation against SCP specifications. Cursor implements; Architect AI governs.

---

## 1. Separation of Concerns

| Agent | Role | Reads | Produces |
|-------|------|-------|----------|
| **Cursor** | Implementation engineer | Task spec + module docs + volumes | Code, tests |
| **Architect AI** | Governance / review | All architecture docs + diff + standards | Pass/fail report, ADR suggestions |
| **Human architect** | Final authority on ADRs | Architect AI report | Approve/reject merge |

---

## 2. Architect AI Responsibilities

### 2.1 Specification Compliance

- Verify changed files match cited task spec and volume chapters
- Flag features not traced to FR/NFR/ADR/Document ID
- Detect missing acceptance criteria coverage in tests

### 2.2 Platform OS Boundary Enforcement

- No `Platform/*` imports from `Modules/*`
- No direct `Connectors/*` usage outside FSL/integration contracts
- No domain logic in `apps/*`
- Validate `module.json` requires graph

### 2.3 Duplication Detection

- Identify new code that duplicates existing Platform Services
- Suggest reuse of FSL, Notifications, Intelligence APIs

### 2.4 Documentation Completeness

On PR, verify:

- [ ] Module `API.md` matches routes
- [ ] `EVENTS.md` matches dispatched events
- [ ] `DATABASE.md` matches migrations
- [ ] `CHANGELOG.md` updated on package change
- [ ] ADR filed if architecture changed

### 2.5 Standards Enforcement

Check against [Engineering Standards](./engineering-standards.md):

- File size limits
- Action/DTO/repository patterns
- Strict typing
- Policy on new routes

### 2.6 Dependency Rules

- Validate composer.json / module.json dependencies
- Flag circular requires
- Ensure Connectors don't depend on Commerce

---

## 3. Inputs

| Input | Source |
|-------|--------|
| Git diff | PR branch |
| Task ID | PR description |
| Read list | Task spec |
| Package manifest | `module.json` |
| Architecture corpus | `V2.0/docs/` (indexed) |
| Standards | `engineering-standards.md` |
| Knowledge graph | `implementation-knowledge-graph.md` |

---

## 4. Outputs

### PR Review Report (structured)

```markdown
## Architect AI Review — SCP-TASK-2026-0101

**Verdict:** PASS | PASS WITH NOTES | BLOCK

### Spec compliance
- [x] Matches Vol 5 Ch. 01 §5 API
- [ ] Missing: ProductUpdated event (spec §7)

### Boundary violations
- None | [list]

### Documentation gaps
- API.md missing DELETE /products/{id}

### Standards
- CreateProductAction: OK
- ProductController: 215 lines — EXCEEDS 200 limit

### Recommendations
1. Add ProductUpdated event
2. Split controller
3. Update API.md
```

---

## 5. Implementation Phases

| Phase | Capability |
|-------|------------|
| **Phase 1** | Manual checklist + Cursor rules (current) |
| **Phase 2** | CI script: deptrac boundaries + doc link checker |
| **Phase 3** | Architect AI agent on PR (Intelligence Platform skill) |
| **Phase 4** | Pre-commit local Architect AI via `scp` CLI |

### Phase 2 CI checks (build first)

```text
bin/architect-check
  ├── boundary-imports.php      # Platform OS rules
  ├── module-manifest.php       # module.json valid
  ├── openapi-sync.php          # routes match spec
  └── required-docs.php         # package docs exist
```

### Phase 3 Agent design

- Skill package: `AI/ArchitectAgent/`
- Tools: read file, grep repo, parse PHP AST, compare OpenAPI
- Memory: none cross-tenant; PR-scoped context only
- Prompt: `V2.0/docs/00-meta/prompts/architect-agent.md`
- Does **not** write production code — review only

---

## 6. Prompt Location

Architect AI system prompt lives in:

`V2.0/docs/00-meta/prompts/architect-agent.md`

Versioned; referenced by agent runtime as `architect-agent@v1`.

---

## 7. When to Escalate to Human

- Proposed ADR or spec change
- Security-sensitive paths (auth, payments, impersonation)
- First package in a new Platform OS layer
- Verdict BLOCK with disputed finding

---

## 8. Acceptance Criteria (Architect AI Program)

- [ ] Phase 2 CI checks in implementation repo
- [ ] `architect-agent.md` prompt published
- [ ] `AI/ArchitectAgent/` skill scaffolded
- [ ] PR template includes Architect AI verdict section
- [ ] False positive rate < 10% on pilot PRs

---

## References

- [Engineering Knowledge Base](./engineering-knowledge-base.md)
- [ADR-020 — Intelligence Platform](./adr/020-sapphital-intelligence-platform.md)
- [Vol 9 — AI Platform](../09-ai-platform/README.md)
