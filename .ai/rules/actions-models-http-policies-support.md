---
paths:
  - 'app/{Actions,Models,Http,Policies,Support}/**'
---

# Actions Models Http Policies Support

## Validate check-ins atomically
Resolve member and membership through the active GymContext. Lock the member, require an active member and gym-local active membership, and reject only check-ins within the preceding five minutes. Store checked_in_at in UTC and audit checkin.created.
