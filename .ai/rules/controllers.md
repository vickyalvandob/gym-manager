---
paths:
  - 'app/Http/Controllers/**'
  - app/Http/Controllers/MemberController.php
---

# Controllers

## Avoid callable names for deferred prop groups
Do not name an Inertia deferred prop group `report` or another PHP callable. Inertia test helpers use is_callable() to distinguish a callback, so use a non-callable group such as `reports`.

## Defer member history payloads
Keep the member operational summary and action eligibility immediate. Load memberships, check-ins, PT package history, and PT session history together in the Inertia deferred group memberHistory, with client loading and retry states.
