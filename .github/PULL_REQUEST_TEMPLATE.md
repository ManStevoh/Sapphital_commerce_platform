## Summary

<!-- What does this PR implement? Link task ID: SCP-TASK-YYYY-NNNN -->

## Specification references

<!-- Document IDs read before implementation -->

- [ ] Task spec: SCP-TASK-
- [ ] Volume / ADR:
- [ ] Module docs read: `[Package]/docs/ARCHITECTURE.md`

## Read list acknowledged

<!-- Copy from task spec — confirms doc-first workflow -->

- [ ] `V2.0/docs/00-meta/engineering-standards.md`
- [ ] [other paths]

## Type of change

- [ ] Feature (spec-driven)
- [ ] Bug fix
- [ ] Documentation only
- [ ] Architecture (ADR attached)

## Checklist

- [ ] Acceptance criteria from task spec met
- [ ] Tests added/updated (Pest / Vitest / Playwright as applicable) and **all passing**
- [ ] Full user flow verified — no white screen, no unhandled frontend errors
- [ ] No new HTTP 500 on touched backend routes (expected errors use 4xx)
- [ ] Tenant isolation tests pass (if data access changed)
- [ ] Module `CHANGELOG.md` updated (if package version bump)
- [ ] `docs/API.md` / `EVENTS.md` / `DATABASE.md` synced (if contracts changed)
- [ ] OpenAPI updated (if public API changed)
- [ ] ADR added/updated (if architecture changed)
- [ ] No secrets, debug dumps, or unauthorized cross-package imports
- [ ] Performance: pagination, indexes, no sync bulk jobs in HTTP path (Engineering Standards §13)
- [ ] Security review requested (auth / payments / tenant / encryption)

## Architect AI / Reviewer notes

<!-- Phase 3: paste Architect AI verdict. Phase 1: module owner sign-off -->

**Module owner:**

## Rollback plan

<!-- Required for non-backward-compatible migrations -->
