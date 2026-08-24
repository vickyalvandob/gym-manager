# GymFlow

GymFlow adalah aplikasi manajemen operasional gym berbasis Laravel, Inertia, React, TypeScript, dan Tailwind CSS. Arsitektur tenant menggunakan satu database dengan shared schema dan isolasi data berdasarkan gym aktif.

## Completed Phases

### Phase 1 - Tenant Foundation

- Authentication melalui Laravel Fortify.
- Registrasi atomik untuk User, Gym, role Owner, dan audit log awal.
- Relasi many-to-many User dan Gym dengan role Owner, Admin/Front Desk, atau Trainer.
- `GymContext` request-scoped dan middleware pemilih gym aktif.
- Policy gym untuk akses tenant, pengelolaan gym, user, dan operasional front desk.
- Dashboard terlindungi, layout aplikasi responsif, sidebar mobile, dan identitas GymFlow.
- Shared Inertia props untuk gym aktif, role, dan izin UI.
- Seeder development untuk akun Owner, Front Desk, dan Trainer.
- Proteksi production untuk perintah database destruktif dan demo seeder.

### Phase 2 - Member Management

- Profil member tenant-scoped dengan nomor otomatis `MBR-000001` per gym.
- Daftar member dengan pencarian nomor/nama/telepon, filter status, dan pagination server-side.
- Tambah, detail, edit, aktivasi, dan deaktivasi tanpa hard delete.
- Policy backend untuk Owner dan Front Desk; Trainer tidak mendapat akses pengelolaan member.
- Foto member tervalidasi dan disimpan pada private storage dengan endpoint terotorisasi.
- Audit log untuk pembuatan, perubahan profil, dan perubahan status member.

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

Menyimpan event penting per gym, actor, polymorphic subject, properti tambahan, dan alamat IP. Event aktif mencakup `user.created`, `member.created`, `member.updated`, dan `member.status_changed`.

### members

Menyimpan profil member per gym. Unique key `(gym_id, member_number)` menjaga nomor member tetap unik di dalam tenant, sementara `gyms.next_member_sequence` dikunci saat alokasi nomor untuk mencegah duplikasi pada request bersamaan.

## Business Rules

- Hanya user dengan membership gym aktif yang dapat membuka dashboard.
- Gym berstatus suspended dan pivot user berstatus inactive tidak dapat menjadi current gym.
- Manipulasi session ke gym tenant lain diabaikan dan tidak membuka akses.
- Owner dapat mengubah gym dan mengelola user; Front Desk hanya mendapat akses operasional; Trainer mendapat workspace terbatas.
- Public registration membuat user dan satu gym baru dalam satu database transaction.
- Demo credential hanya dibuat pada environment `local` dan `testing`.
- Nomor member dihasilkan backend dari sequence gym dan tidak menerima `gym_id` atau nomor dari frontend.
- Member tidak dihapus permanen dari workflow operasional; gunakan status aktif/nonaktif.
- Semua lookup detail, edit, status, dan foto dilakukan melalui relasi member milik `GymContext` aktif.

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
- Statistik dashboard terhubung ke data operasional pada Phase 7.
- Riwayat paket, pembayaran, dan check-in pada detail member tersedia setelah Phase 4-6.
- Pengelolaan staff dan pengaturan profil gym masuk phase lanjutan.
- SaaS billing, subscription platform, dan Super Admin belum diimplementasikan.

## Remaining For Phase 3

Phase 3 mencakup CRUD paket membership, durasi, harga, status aktif/nonaktif, dan tenant isolation.
