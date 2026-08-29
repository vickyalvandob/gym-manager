---
paths:
  - 'app/{Actions,Http,Models,Policies,Support}/**'
---

# Actions Http Models Policies Support

## Pisahkan otorisasi platform dan tenant
Super Admin adalah identitas platform melalui users.is_platform_admin, bukan role gym_user. Route platform tidak memakai GymContext; seluruh operasi gym tetap ditentukan dan dibatasi backend melalui GymContext. Subscription SaaS terpisah dari pembayaran operasional member dan PT.
