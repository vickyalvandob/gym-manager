# Gymlo

> Platform manajemen gym **freemium tanpa trial** untuk member, membership, pembayaran, QR check-in, trainer, Personal Training, dan multi-outlet.

Gymlo menghubungkan operasional Front Office, pengawasan Owner, agenda Trainer, dan pengalaman Member dalam satu aplikasi. Registrasi membuat akun Owner dan outlet pertama dengan **Free Plan aktif tanpa tanggal kedaluwarsa**. Upgrade dilakukan ketika kebutuhan kapasitas bertambah.

## Area aplikasi

| Area | Pengguna | Fokus |
| --- | --- | --- |
| `/app` | Owner dan Front Office | Operasional gym, member, membership, transaksi, dan check-in |
| `/trainer` | Trainer | Client sendiri, jadwal, hasil sesi, dan riwayat PT |
| `/my` | Member | My Gymlo: membership, QR, pembayaran, dan PT pribadi |
| `/platform` | Platform Admin | Paket SaaS, tenant, akses akun, dan verifikasi pembayaran langganan |

Login dan tombol masuk dari landing page memilih area berdasarkan akses pengguna di backend. Perpindahan outlet mempertahankan tujuan sesuai role pada outlet tersebut. Pengguna tidak memperoleh izin ke area lain hanya dengan mengubah URL.

Owner dan Front Office memakai workspace manajemen yang sama dengan menu serta izin berbeda. Nama internal role Front Office tetap `GymRole::Admin` / `admin` agar kompatibel dengan data yang ada. Akun Member terhubung melalui `members.user_id`, bukan sebagai staf pada `gym_user`.

## Freemium dan paket berbayar

Alur pendaftaran:

1. Daftarkan nama Owner, email, password, dan nama gym.
2. Backend membuat Owner, gym, role, subscription Free, dan audit dalam satu transaksi.
3. Lengkapi profil gym melalui onboarding. Mata uang operasional adalah IDR.
4. Gunakan fitur inti di dalam kapasitas paket, tanpa hitung mundur trial.
5. Owner membuka **Paket & pembayaran** untuk memilih upgrade.

Pilihan paket dari browser tidak dapat mengganti Free Plan saat registrasi. Registrasi memerlukan paket `free` yang aktif dan berharga nol dalam katalog.

### Katalog awal

Nilai berikut adalah default `SaasPlanSeeder`. Katalog aktual dapat diubah Platform Admin; landing page membaca harga dan kapasitas langsung dari database, **hanya untuk paket berbayar yang aktif**.

| Kapasitas | Free | Starter | Growth | Scale |
| --- | ---: | ---: | ---: | ---: |
| Harga per bulan | Rp0 | Rp299.000 | Rp599.000 | Rp999.000 |
| Outlet | 1 | 1 | 3 | Tanpa batas |
| Member | 20 | 500 | 2.000 | Tanpa batas |
| Staf, termasuk Trainer | 5 | 10 | 30 | Tanpa batas |
| Trainer | 5 | 10 | 30 | Tanpa batas |
| Paket membership | 5 | 20 | 50 | Tanpa batas |
| Paket PT | 5 | 20 | 50 | Tanpa batas |

Semua paket mencakup fitur operasional inti, Trainer, Personal Training, My Gymlo, dan laporan Owner. Akses fitur tetap mengikuti peran pengguna.

Batas berasal dari `saas_plans`, bukan konstanta dalam controller. Kolom `max_gyms` merepresentasikan jumlah outlet; limit lainnya adalah `max_members`, `max_staff`, `max_trainers`, `max_membership_plans`, dan `max_pt_packages`. Nilai `null` berarti tanpa batas.

`SubscriptionQuota` menghitung penggunaan gabungan seluruh outlet dalam subscription. Staf dihitung sebagai akun unik non-Owner; Trainer juga memakai kuota staf. Member, profil Trainer, dan katalog paket dihitung termasuk data nonaktif agar nonaktif/aktif tidak menjadi jalan melewati batas. Pembuatan resource memeriksa limit dalam transaksi yang mengunci subscription.

Pada database yang di-upgrade, batas baru Free diisi lima Trainer, lima paket membership, dan lima paket PT. Batas baru paket berbayar existing tetap `null` sampai dikonfigurasi Platform Admin. Migrasi tidak menimpa harga atau batas lama yang sudah dikustomisasi.

### Upgrade, masa aktif, dan downgrade

- Owner memilih paket berbayar, mengikuti instruksi rekening platform, lalu mengunggah satu bukti transfer privat.
- Nominal, mata uang, dan periode tagihan diambil dari katalog backend.
- Pembayaran berstatus pending sampai disetujui Platform Admin. Unggah bukti belum mengaktifkan paket.
- Persetujuan mengaktifkan atau memperpanjang subscription secara atomik dan mencatat reviewer serta audit.
- Pembayaran yang sudah ditinjau tidak dapat disetujui dua kali.
- Subscription berbayar yang habis membatasi operasional; Owner tetap dapat membuka billing. `past_due` memiliki grace period melalui `SUBSCRIPTION_GRACE_DAYS`, default tujuh hari.
- Downgrade manual hanya diterima jika paket tujuan mencukupi seluruh penggunaan existing. Sistem tidak menghapus outlet, member, transaksi, atau histori untuk memaksakan penurunan paket.
- Pembuatan trial baru tidak tersedia. Status trial lama tetap dibaca untuk kompatibilitas data historis.

Pembayaran subscription SaaS terpisah dari invoice membership dan PT milik gym.

## Fitur dan workflow operasional

### Member dan My Gymlo

- Nomor `MBR-000001` berurutan per outlet dan dialokasikan backend dengan row lock.
- Profil, foto privat, kontak darurat, catatan, status aktif/nonaktif, pencarian, dan pagination server-side.
- Riwayat membership, pembayaran, kunjungan, assignment Trainer, dan sesi PT tetap tersimpan saat member dinonaktifkan.
- Owner/Front Office mengaktifkan akun portal dari profil member. Registrasi publik digunakan untuk Owner, bukan untuk membuat member gym sendiri.
- Member hanya membuka data yang terhubung ke akunnya. Bila memiliki beberapa profil, pemilihan profil tetap diverifikasi di backend.
- My Gymlo menyediakan dashboard pribadi, QR, membership aktif dan histori, invoice pribadi, jadwal serta kuota PT, profil, dan keamanan akun.

### Membership, invoice, dan pembayaran

1. Owner/Front Office memilih member dan paket membership aktif.
2. Backend memvalidasi outlet dan periode, menolak overlap, lalu menyimpan snapshot nama, durasi, dan harga.
3. Satu invoice pending dibuat untuk periode tersebut. Membership belum memberi hak check-in sebelum dibayar.
4. Front Office mengonfirmasi pembayaran penuh dengan metode yang valid.
5. Invoice menjadi paid dan membership diaktifkan dalam transaksi yang sama. Check-in diizinkan bila tanggal lokal gym berada dalam periode aktif.
6. Renewal membuat periode dan invoice baru; periode lama tetap menjadi histori.

Durasi mendukung hari, minggu, bulan, dan tahun. Status dihitung dari aktivasi dan tanggal lokal gym: pending, upcoming, active, atau expired, dengan indikator segera berakhir. Harga menggunakan `decimal(14,2)` dan nominal invoice selalu berasal dari snapshot backend.

Metode pembayaran: tunai, transfer bank, kartu debit, kartu kredit, dan e-wallet. Pembayaran lunas tidak dapat diproses ulang. Revenue hanya berasal dari transaksi paid berdasarkan `paid_at`.

### QR check-in

1. Member membuka `/my/qr`.
2. Front Office memasukkan hasil scan QR pada halaman check-in, atau mencari member secara manual.
3. Backend memeriksa identitas QR, outlet aktif, status member, pembayaran dan masa berlaku membership.
4. Check-in dicatat dengan membership yang digunakan saat validasi, waktu UTC, operator, dan audit.
5. Riwayat kunjungan tampil di My Gymlo.

QR adalah identifier, bukan bukti hak masuk. QR dari outlet lain atau token yang tidak cocok ditolak. Duplikasi dalam lima menit ditolak tanpa membatasi kunjungan sah berikutnya pada hari yang sama. Input scan mendukung hasil pembaca QR; pemindaian kamera langsung belum disediakan.

### Trainer dan Personal Training

- Owner membuat profil Trainer bersama akun login individual; kode Trainer berurutan per outlet.
- Owner/Front Office mengatur assignment client. Riwayat assignment aktif dan berakhir dipertahankan.
- Owner mengelola katalog PT; Front Office dapat melihat dan menjual paket aktif.
- Pembelian PT mencatat pembayaran penuh, snapshot paket, assignment Trainer, kuota, dan audit dalam satu transaksi.
- Member dapat memiliki satu paket PT aktif. Paket baru dapat dibeli setelah paket sebelumnya selesai atau berakhir sesuai aturan backend.
- Penjadwalan hanya menerima paket berbayar yang masih berlaku, member aktif, Trainer aktif, dan kuota tersedia.
- Sesi terjadwal memesan kuota. Backend menolak waktu lampau, bentrok Trainer, dan bentrok jadwal member, termasuk saat reschedule.
- Completed mengonsumsi kuota; Cancelled melepaskan reservasi. No-show mengikuti pengaturan gym.
- Owner dan Trainer terkait dapat mencatat hasil sesi. Front Office menangani penjadwalan/reschedule/cancel sesuai izin backend.
- Trainer hanya membuka client dan sesi yang menjadi tanggung jawabnya, tanpa pembayaran atau revenue.

### Dashboard dan laporan

- **Owner:** revenue, member, retensi membership, pembayaran tertunda, check-in, dan aktivitas operasional.
- **Front Office:** pelayanan dan tindak lanjut harian tanpa revenue khusus Owner.
- **Trainer:** agenda hari ini, sesi mendatang, client aktif, dan sisa kuota.
- **Member:** membership pribadi, QR, jadwal PT, sisa sesi, pembayaran, dan kunjungan.
- Laporan Owner mencakup revenue, status member/membership, kunjungan, serta aktivitas terkait pada outlet aktif.
- Filter laporan menyediakan periode umum atau rentang kustom maksimal 366 hari.
- Snapshot dashboard, laporan, dan histori member menggunakan deferred props dengan loading, empty, error, dan retry.

### Multi-outlet

Owner membuka `/app/outlets` untuk melihat atau menambah outlet dan berpindah konteks kerja. Outlet baru menggunakan subscription Owner yang sama dan menjalani onboarding sendiri. Free dibatasi satu outlet; outlet kedua memerlukan kapasitas yang mencukupi.

Implementasi menggunakan satu baris `gyms` per outlet, terhubung melalui `gyms.subscription_id`. Member, transaksi, katalog, Trainer, dan laporan tetap terpisah berdasarkan `gym_id`; belum ada membership lintas-outlet atau laporan konsolidasi semua outlet.

## Matriks hak akses

| Kapabilitas | Owner | Front Office | Trainer | Member |
| --- | --- | --- | --- | --- |
| Area utama | `/app` | `/app` | `/trainer` | `/my` |
| Kelola member dan membership | Ya | Ya | Client sendiri, baca | Milik sendiri, baca |
| Katalog membership | Ya | Ya | Tidak | Tidak |
| Proses pembayaran dan check-in | Ya | Ya | Tidak | QR dan histori sendiri |
| Kelola Trainer dan katalog PT | Ya | Lihat/jual PT | Tidak | Tidak |
| Assignment Trainer | Ya | Ya | Tidak | Baca |
| Jadwalkan/reschedule/cancel PT | Ya | Ya | Sesi sendiri | Baca |
| Complete/no-show PT | Ya | Tidak | Sesi sendiri | Baca |
| Laporan dan revenue | Ya | Tidak | Tidak | Tidak |
| Staf, outlet, profil gym | Ya | Tidak | Tidak | Tidak |
| Subscription | Owner subscriber | Tidak | Tidak | Tidak |
| Profil dan keamanan akun | Sendiri | Sendiri | Sendiri | Sendiri |

Policy, Form Request, dan middleware menegakkan izin ini di backend. Permission React mengatur visibilitas UI. Platform Admin memakai `users.is_platform_admin` dan tidak otomatis menjadi pengguna operasional setiap gym.

## Route utama

```text
/app
/app/dashboard
/app/onboarding
/app/members
/app/membership-plans
/app/payments
/app/check-ins
/app/trainers
/app/pt-packages
/app/pt-sessions
/app/reports
/app/staff
/app/outlets
/app/subscription
/app/settings/gym

/trainer
/trainer/clients
/trainer/schedule
/trainer/sessions
/trainer/history
/trainer/profile

/my
/my/qr
/my/membership
/my/check-ins
/my/payments
/my/personal-training
/my/profile
/my/security
```

Endpoint `/portal-member/{member}` dipertahankan untuk kompatibilitas tautan lama dengan pemeriksaan ownership. Navigasi baru Member menggunakan `/my`. Pengaturan keamanan akun bersama masih menggunakan endpoint autentikasi di `/settings`.

## Arsitektur dan integritas data

Satu modern monolith Laravel + Inertia melayani seluruh area. Setiap request operasional melewati autentikasi, konteks gym, batas workspace, onboarding/subscription, dan policy resource.

| Layer | Tanggung jawab |
| --- | --- |
| Middleware | Tenant aktif, role workspace, onboarding, lifecycle subscription |
| Form Request | Normalisasi input, validasi, otorisasi request |
| Controller | Orkestrasi request dan payload Inertia |
| Policy | Akses role, tenant, dan ownership resource |
| Action | Business rule, transaksi, row lock, audit |
| Support | `GymContext`, `SubscriptionQuota`, eligibility, scheduler, snapshot |
| React | Form, navigasi, loading/error, dan tampilan responsif |

`current_gym_id` di session selalu diperiksa terhadap relasi aktif pengguna. Backend tidak mempercayai `gym_id`, nomor member, nomor invoice, atau nominal pembayaran dari browser. Perubahan finansial dan kuota memiliki transaction boundary; histori dan snapshot tidak mengikuti perubahan master data. File privat hanya disajikan melalui endpoint berotorisasi.

## Tech stack

PHP 8.4, Laravel 13, Fortify, MySQL, Inertia.js 3, React 19, TypeScript, Tailwind CSS 4, Radix UI, Laravel Wayfinder, dan Vite 8. Pengujian memakai Pest 5 dengan SQLite in-memory; pemeriksaan statis memakai PHPStan/Larastan dan TypeScript. Pint, ESLint, Prettier, serta GitHub Actions melengkapi quality checks.

Halaman tetap mengikuti struktur modul existing di `resources/js/pages`, dengan `app-layout.tsx`, `trainer-layout.tsx`, dan `member-portal-layout.tsx`. Nama folder React tidak harus mengikuti prefix URL. Generated routes dan actions Wayfinder tidak diedit manual.

## Menjalankan secara lokal

Prasyarat: PHP 8.4, Composer 2, Node.js 22, npm, dan MySQL/MariaDB yang kompatibel.

```powershell
composer install
npm.cmd install
Copy-Item .env.example .env
php artisan key:generate --no-interaction
# Sesuaikan koneksi database pada .env terlebih dahulu.
php artisan migrate --seed --no-interaction
php artisan wayfinder:generate --with-form --no-interaction
npm.cmd run build
composer run dev
```

Linux/macOS menggunakan `cp .env.example .env` dan `npm` tanpa suffix `.cmd`.

Untuk memperbarui instalasi existing, gunakan `php artisan migrate --no-interaction`, regenerasi Wayfinder, lalu build frontend. Jangan memakai `migrate:fresh` pada data yang ingin dipertahankan. `SaasPlanSeeder` memperbarui paket default; jangan jalankan ulang pada katalog yang sudah dikustomisasi tanpa meninjau nilainya.

Demo seeder dibatasi environment local/testing. Akun demo mencakup `platform@gym.test`, `owner@gym.test`, `frontdesk@gym.test`, dan `andi@gym.test`, dengan password `password`. Akses Member diaktifkan dari detail member oleh operator; jangan menganggap setiap member demo sudah memiliki akun portal. Credential demo tidak digunakan di production.

## Pengujian

```powershell
php artisan wayfinder:generate --with-form --no-interaction
php artisan test --compact
php vendor/bin/pint --dirty --format agent
php vendor/bin/phpstan analyse --no-progress
npm.cmd run lint:check
npm.cmd run format:check
npm.cmd run types:check
npm.cmd run build:ssr
```

`composer ci:check` menggabungkan pemeriksaan frontend, Pint, PHPStan, dan Pest. Build dijalankan terpisah.

Cakupan utama: registrasi Free, trial historis, masa aktif subscription, onboarding idempotent, isolasi tenant/area/member/Trainer, kuota lintas-outlet, harga landing dari database, pembayaran manual subscription, membership pending/lunas, QR check-in, snapshot paket PT, konflik jadwal, konsumsi kuota, dan audit. Browser QA melengkapi feature tests untuk menilai navigasi dan tampilan responsif; backend tests saja tidak membuktikan tampilan browser.

## Batasan dan pengembangan berikutnya

- Billing subscription menggunakan transfer manual dan review Platform Admin; belum ada payment gateway, webhook, proration, atau auto-renewal.
- Downgrade belum self-service dan tidak otomatis saat paket berbayar berakhir.
- Pembayaran operasional belum mencakup cicilan, refund, atau pembatalan invoice.
- Belum ada membership lintas-outlet, laporan konsolidasi outlet, kelas grup, workout plan, inventory, payroll/komisi Trainer, atau notifikasi WhatsApp otomatis.
- Deployment memerlukan HTTPS, konfigurasi production, storage privat, backup/restore yang diuji, dan UAT operasional. Keberhasilan test/build lokal bukan bukti kesiapan infrastruktur production.
