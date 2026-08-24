# GymFlow

GymFlow adalah aplikasi manajemen operasional gym berbasis Laravel, Inertia, React, TypeScript, dan Tailwind CSS. Arsitektur tenant menggunakan satu database dengan shared schema dan isolasi data berdasarkan gym aktif.

## Phase 1 Completed

- Authentication melalui Laravel Fortify.
- Registrasi atomik untuk User, Gym, role Owner, dan audit log awal.
- Relasi many-to-many User dan Gym dengan role Owner, Admin/Front Desk, atau Trainer.
- `GymContext` request-scoped dan middleware pemilih gym aktif.
- Policy gym untuk akses tenant, pengelolaan gym, user, dan operasional front desk.
- Dashboard terlindungi, layout aplikasi responsif, sidebar mobile, dan identitas GymFlow.
- Shared Inertia props untuk gym aktif, role, dan izin UI.
- Seeder development untuk akun Owner, Front Desk, dan Trainer.
- Proteksi production untuk perintah database destruktif dan demo seeder.

## Architecture

```text
Browser -> React -> Inertia -> Laravel -> MySQL/MariaDB
                                     |
                                     -> GymContext -> gym_id scoped queries
```

`current_gym_id` disimpan di session, tetapi selalu diverifikasi terhadap membership aktif milik user. Nilai tenant dari request atau frontend tidak digunakan sebagai sumber otoritatif. Model operasional pada phase berikutnya wajib memiliki `gym_id` dan harus diakses melalui gym yang telah diselesaikan middleware.

## Database Changes

### gyms

Menyimpan identitas tenant, status, timezone, currency, dan batas peringatan membership.

### gym_user

Pivot User-Gym dengan unique key `(gym_id, user_id)`, role, status, timestamp, dan index untuk membership aktif.

### activity_logs

Menyimpan event penting per gym, actor, polymorphic subject, properti tambahan, dan alamat IP. Phase 1 mencatat `user.created` saat registrasi.

## Business Rules

- Hanya user dengan membership gym aktif yang dapat membuka dashboard.
- Gym berstatus suspended dan pivot user berstatus inactive tidak dapat menjadi current gym.
- Manipulasi session ke gym tenant lain diabaikan dan tidak membuka akses.
- Owner dapat mengubah gym dan mengelola user; Front Desk hanya mendapat akses operasional; Trainer mendapat workspace terbatas.
- Public registration membuat user dan satu gym baru dalam satu database transaction.
- Demo credential hanya dibuat pada environment `local` dan `testing`.

## Test Accounts

Semua akun development menggunakan password `password`.

| Peran | Email |
| --- | --- |
| Owner | `owner@gym.test` |
| Front Desk | `frontdesk@gym.test` |
| Trainer | `trainer@gym.test` |

## Local Setup

```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
composer run dev
```

Pastikan konfigurasi database MySQL/MariaDB pada `.env` sudah benar sebelum menjalankan migration.

## Verification

```bash
php artisan test --compact
composer run types:check
npm run lint:check
npm run format:check
npm run types:check
npm run build
```

## Production Checklist

- Gunakan `APP_ENV=production` dan `APP_DEBUG=false`.
- Gunakan secret `APP_KEY` yang unik dan database credential production.
- Jalankan migration dengan `php artisan migrate --force`, tanpa demo seeder.
- Konfigurasikan HTTPS, queue worker, scheduler, mail transport, backup off-site, dan monitoring log.
- Jalankan `php artisan optimize` setelah deployment dan `php artisan optimize:clear` saat konfigurasi berubah.
- Lakukan UAT tenant isolation menggunakan minimal dua gym sebelum go-live.

## Known Limitations

- Selector untuk user multi-gym belum tersedia; middleware memakai gym aktif pertama atau gym session yang valid.
- Dashboard Phase 1 menampilkan empty state karena tabel member, membership, payment, dan check-in belum dibuat.
- Pengelolaan staff dan pengaturan profil gym masuk phase lanjutan.
- SaaS billing, subscription platform, dan Super Admin belum diimplementasikan.

## Remaining For Phase 2

Phase 2 mencakup schema member, nomor member unik per gym, CRUD, pencarian, filter, pagination, aktivasi/deaktivasi, detail member, authorization, dan regression test tenant isolation.
