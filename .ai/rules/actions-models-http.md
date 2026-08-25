---
paths:
  - 'app/{Actions,Models,Http}/**'
---

# Actions Models Http

## Keep membership periods immutable and non-overlapping
Store each assignment or renewal as a member_memberships history row with plan snapshots. Derive upcoming, active, and expired status from gym-local start_date and end_date instead of persisting a stale status. Lock the member and reject overlapping periods; membership plans with history cannot be deleted.
