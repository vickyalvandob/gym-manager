---
paths:
  - 'app/{Actions,Http,Models,Support}/**'
---

# Actions Http Models Support

## Enforce subscriber quotas across shared gyms
A SaaS subscription belongs to one subscriber and may be shared by gyms through gyms.subscription_id. Enforce max_gyms, max_members, and max_staff as aggregate subscription quotas inside transactions that lock the subscription row. Free remains one gym, 20 members, and 5 non-owner staff.
