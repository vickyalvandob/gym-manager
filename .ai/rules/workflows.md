---
paths:
  - '.github/workflows/**'
---

# Workflows

## Generate Wayfinder before frontend checks
Wayfinder output directories are gitignored. In clean CI, run `php artisan wayfinder:generate --with-form --no-interaction` after application preparation and before ESLint or TypeScript checks, otherwise `@/actions` and `@/routes` imports are unresolved or misclassified.
