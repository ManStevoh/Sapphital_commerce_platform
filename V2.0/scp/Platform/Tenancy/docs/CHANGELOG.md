# Changelog — Platform Tenancy

All notable changes to `platform/tenancy` follow [Semantic Versioning](https://semver.org/).

## [0.1.0] — 2026-07-12

### Added

- Initial Sprint 0 package scaffold (ADR-023, module template)
- `TenancyServiceProvider` — registers migrations and API routes
- `GET /api/v1/platform/tenancy/health` health probe
- Placeholder `tenants` table migration (no RLS)
- Feature test for health endpoint
- Module contract docs (`README`, `ARCHITECTURE`, `DATABASE`, `API`, `TESTING`)
