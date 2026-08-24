---
paths:
  - 'app/{Models,Http,Actions,Policies,Support}/**'
---

# Models Http Actions Policies Support

## Scope operational data through GymContext
Resolve the active tenant with SetCurrentGym and GymContext. Never trust gym_id from frontend input or use an unscoped operational query. Authorize the resolved gym on the backend; shared Inertia permissions only control UI visibility.
