---
paths:
  - 'app/{Actions,Models,Http,Policies}/**'
---

# Actions Models Http Policies

## Treat membership invoices as immutable full-payment records
Create exactly one payment invoice per member_membership using the membership price snapshot. Allocate invoice numbers from the current Gym row while it is locked, and never trust a submitted gym_id or amount. Payments only transition pending to paid with a validated method, backend paid_at, receiver, and audit event; do not add partial, refund, cancellation, or direct financial edits without an explicit new phase contract.
