# Changelog — Platform Identity

All notable changes to `platform/identity` follow [Semantic Versioning](https://semver.org/).

## [0.3.0] — 2026-07-14

### Added

- Merchant Owner MFA (Totp enrollment, challenge, backup codes) — `MERCHANT_MFA_ENFORCED`
- `MerchantMfaService` / `MerchantMfaController` (`/api/v1/auth/merchant/mfa/*`)
- Session & API token management (`/api/v1/auth/merchant/sessions`)
- Login notification on new full-access session (`MerchantLoginNotifier`)
- `merchant_users.mfa_confirmed_at` / `mfa_backup_codes`

### Changed

- Merchant login/handoff issue MFA pending tokens for Owners when MFA is enforced
- `EnsureMerchantTenant` rejects tokens without `merchant:access` (or `*`)

## [0.1.0] — 2026-07-12

### Added

- Initial Sprint 0 package scaffold (ADR-023, module template)
- `IdentityServiceProvider` — registers API routes
- `GET /api/v1/platform/identity/health` health probe
- Module contract docs (`README`, `ARCHITECTURE`, `API`)
