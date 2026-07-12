# SAPPHITAL Commerce Platform (SCP)

**Version:** 1.0 Architecture Specification  
**Owner:** Sapphital Learning Company  
**Lead Software Architect:** Stephen Musyoka Makola  
**Status:** Active — Architecture Program  
**Classification:** Internal Engineering Reference (Public Docs Planned)

---

## Overview

The SAPPHITAL Commerce Platform (SCP) is an AI-native, multi-tenant, enterprise SaaS commerce operating system designed for African SMEs and architected for global expansion. This repository contains the official engineering specification — not application source code.

This documentation follows the same rigor used by world-class engineering organizations: architecture decision records, evidence-based technology selection, domain-driven design, and traceable requirements from vision to implementation.

## Documentation Structure

| Volume | Title | Status |
|--------|-------|--------|
| 0 | Meta & Engineering Principles | ✅ Active |
| 1 | Vision & Product Strategy | ✅ Active |
| 2 | Market Research & Technology Strategy | ✅ Active |
| 3 | System Architecture | ✅ Active |
| 4 | SAPPHITAL Design System | ✅ Active |
| 5 | Commerce Core Engine | ✅ Active |
| 6 | Theme Engine | ✅ Active |
| 7 | CMS & Page Builder | ✅ Active |
| 8 | Marketplace | ✅ Active |
| 9 | AI Platform | ✅ Active |
| 10 | Infrastructure & DevOps | ✅ Active |
| 11 | Security & Compliance | ✅ Active |
| 12 | Developer Platform | ✅ Active |
| 13 | Testing & Quality | ✅ Active |
| 14 | Operations & Reliability | ✅ Active |
| 15 | Future Roadmap | ✅ Active |
| 16 | SaaS & Multi-Tenancy | ✅ Active |
| 17 | Database & Data Architecture | ✅ Active |
| 18 | Mobile & POS | ✅ Active |
| 19 | Automation & Integrations | ✅ Active |
| 20 | Legal & Enterprise Readiness | ✅ Active |
| 21 | Implementation Playbooks | ✅ Active |

**Primary market:** Nigeria (NDPA/GAID compliant); Kenya and pan-Africa expansion documented throughout.

See [Synthesis Status](docs/00-meta/synthesis-status.md) for chapter counts (~220+ chapters).

## Quick Start

```text
V2.0/
├── docs/           ← Source of truth (Markdown)
├── docs-site/      ← Docusaurus publishing site
└── README.md       ← This file
```

### Read the Specification

1. Start with [Engineering Principles](docs/00-meta/engineering-principles.md)
2. Read [Volume 1: Vision & Product Strategy](docs/01-vision/README.md)
3. Review [Document Control](docs/00-meta/document-control.md) for versioning policy

### Publish Documentation Site

```bash
cd docs-site
npm install
npm run start    # Local preview at http://localhost:3000
npm run build    # Production build
```

## Engineering Principles (Summary)

- **User Experience First** — Performance and clarity are non-negotiable
- **API-First** — Every feature is an API; UI is one client among many
- **Modular & Decoupled** — Domain boundaries with inward dependencies
- **AI Native** — AI is infrastructure, not a bolt-on
- **Secure by Default** — Threat-modeled from day one
- **Multi-Tenant** — Tenant isolation at every layer
- **Extensible** — Themes, plugins, webhooks, SDKs without core modification
- **Observable** — Logs, metrics, traces, and audits everywhere

## Contributing

All changes to this specification require:

1. Update relevant volume document(s)
2. Create or update ADR if architectural decision changed
3. Update document control version table
4. Peer review before merge to `main`

## License

Copyright © 2026 Sapphital Learning Company. All rights reserved.
