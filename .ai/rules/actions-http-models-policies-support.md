---
paths:
  - 'app/{Actions,Http,Models,Policies,Support}/**'
---

# Actions Http Models Policies Support

## Pisahkan otorisasi platform dan tenant
Super Admin adalah identitas platform melalui users.is_platform_admin, bukan role gym_user. Route platform tidak memakai GymContext; seluruh operasi gym tetap ditentukan dan dibatasi backend melalui GymContext. Subscription SaaS terpisah dari pembayaran operasional member dan PT.

## Pisahkan status akun global dan akses staf per gym
users.is_active adalah status login global yang hanya dikelola Platform Super Admin. Owner mengelola staf pada gym aktif melalui gym_user.status dan GymContext; Front Desk dibuat dari modul Staf, sedangkan akun Trainer tetap dikelola melalui workflow Trainer agar profil dan pivot selalu sinkron. Registrasi mandiri menjadikan subscriber sebagai Owner gym pertama.
