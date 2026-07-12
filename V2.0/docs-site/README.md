# SCP Documentation Site

Docusaurus-powered documentation site for the SAPPHITAL Commerce Platform engineering specification.

## Prerequisites

- Node.js 20 LTS or higher
- npm 10+

## Local Development

```bash
cd docs-site
npm install
npm start
```

Open [http://localhost:3000](http://localhost:3000) to preview.

## Build for Production

```bash
npm run build
npm run serve
```

## Source Files

Markdown source files live in `../docs/`. Edit files there — not in `docs-site/`.

## Deploy to GitHub Pages

Push to `main` branch. GitHub Actions workflow (`.github/workflows/docs.yml`) builds and deploys automatically.

Or manually:

```bash
npm run build
# Deploy build/ directory to gh-pages branch
```

## Configuration

- `docusaurus.config.js` — Site configuration
- `sidebars.js` — Navigation sidebar structure
- `../docs/` — Markdown source files
