---
paths:
  - 'app/Http/Middleware/**'
---

# Middleware

## Keep current gym selected columns in sync
SetCurrentGym uses an explicit gyms column list. Whenever a gym setting is consumed through GymContext or shared Inertia auth props, add that column to the select list and cover it with a request-level test.
