---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Avoid callable names for deferred prop groups
Do not name an Inertia deferred prop group `report` or another PHP callable. Inertia test helpers use is_callable() to distinguish a callback, so use a non-callable group such as `reports`.
