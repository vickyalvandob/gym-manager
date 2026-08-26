---
paths:
  - 'app/{Models,Http,Actions,Policies,Support}/**'
---

# Models Http Actions Policies Support

## Scope operational data through GymContext
Resolve the active tenant with SetCurrentGym and GymContext. Never trust gym_id from frontend input or use an unscoped operational query. Authorize the resolved gym on the backend; shared Inertia permissions only control UI visibility.

## Keep trainer profiles and assignments role scoped
Resolve trainers and assigned members through the active GymContext. Owner manages trainer profiles, Owner and Front Desk manage assignments, and a Trainer may view only the active profile linked to their own user account. Never trust gym_id or expose unassigned members on the Trainer dashboard.
