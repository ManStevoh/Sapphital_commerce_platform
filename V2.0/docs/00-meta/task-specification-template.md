# Task Specification Template

**Document ID:** SCP-META-TASK-001  
**Version:** 1.0.0  
**Status:** ✅ Active  

---

## How to Use

Copy this template for **every** feature, bugfix, or refactor. Store tasks in your issue tracker; paste into Cursor sessions.

---

```markdown
# Task: [Short title]

**Task ID:** SCP-TASK-YYYY-NNNN  
**Author:** [Name]  
**Date:** YYYY-MM-DD  
**Phase:** 1 | 2 | 3  
**Package:** Platform/Identity | Modules/Commerce | apps/storefront | …  
**Priority:** P0 | P1 | P2  

---

## 1. Goal

[One sentence — what merchant or system outcome this enables]

---

## 2. Specification References

| ID | Document | Sections |
|----|----------|----------|
| SCP-COM-005-01 | Vol 5 Ch. 01 Product Catalog | §3 Entities, §5 API |
| ADR-023 | Platform OS | §12 Commerce structure |
| FR-020 | Domain model | tenant_id on all entities |

---

## 3. Read List (Cursor — mandatory before code)

- [ ] `V2.0/docs/00-meta/engineering-standards.md`
- [ ] `V2.0/docs/00-meta/implementation-knowledge-graph.md`
- [ ] `[volume chapter path]`
- [ ] `[Package]/docs/ARCHITECTURE.md`
- [ ] `[Package]/docs/API.md` (update after implementation)

---

## 4. Scope

### In scope
- [ ] …
- [ ] …

### Out of scope
- …
- …

---

## 5. Acceptance Criteria

- [ ] [Testable criterion from volume AC section]
- [ ] [Testable criterion]
- [ ] Unit tests: [list test files]
- [ ] Feature tests: [list]
- [ ] Tenant isolation test passes
- [ ] No N+1 / unbounded queries (Engineering Standards §13)
- [ ] Side effects queued where applicable (§14)
- [ ] Security: policy, tenant scope, rate limit if public (§12)
- [ ] Documentation updated: [API.md, EVENTS.md, …]

---

## 6. API Contract (if applicable)

### `POST /api/v1/...`

**Permission:** `…`  
**Request:** …  
**Response:** …  
**Events:** …  
**Errors:** 422, 403, …  

---

## 7. Database (if applicable)

**Tables:** …  
**RLS:** yes/no  
**Migration file:** `database/migrations/YYYY_MM_DD_…`  

Document in `docs/DATABASE.md` before migrate.

---

## 8. UI (if applicable)

**Screen:** Vol 4 Ch. … / module `docs/UI.md`  
**States:** loading, empty, error, success  
**Permissions:** …  

---

## 9. Dependencies

**Requires packages enabled:** …  
**Blocked by tasks:** SCP-TASK-…  
**Blocks tasks:** …  

---

## 10. Conflict Protocol

If implementation cannot match documentation:

1. Stop coding  
2. Document: spec citation, actual blocker, proposed fix  
3. Fix spec or file ADR before continuing  

---

## 11. Definition of Done

- [ ] All acceptance criteria checked  
- [ ] CI green  
- [ ] PR linked with this task ID  
- [ ] Module CHANGELOG updated if semver bump  
- [ ] Reviewer: module owner  
```

---

## Example Task IDs

| Prefix | Meaning |
|--------|---------|
| SCP-TASK- | General implementation |
| SCP-BUG- | Defect fix |
| SCP-SPIKE- | Time-boxed research (no merge without spec) |
| SCP-DOC- | Documentation-only |

---

## References

- [Cursor Implementation Workflow](./cursor-implementation-workflow.md)
- [Engineering Knowledge Base](./engineering-knowledge-base.md)
