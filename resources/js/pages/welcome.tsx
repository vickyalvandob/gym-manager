import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    Banknote,
    BarChart3,
    Check,
    CheckCircle2,
    Clock3,
    CreditCard,
    Dumbbell,
    Fingerprint,
    ScanLine,
    ShieldCheck,
    Sparkles,
    UserRoundCheck,
    UsersRound,
} from 'lucide-react';
import type { ComponentType } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard, login, register } from '@/routes';
import { dashboard as platformDashboard } from '@/routes/platform';

type Feature = {
    title: string;
    description: string;
    icon: ComponentType<{ className?: string; 'aria-hidden'?: boolean }>;
};

const features: Feature[] = [
    {
        title: 'Member & membership',
        description:
            'Data member, paket, perpanjangan, dan status aktif tersusun dalam satu profil yang mudah dicari.',
        icon: UsersRound,
    },
    {
        title: 'Check-in yang tervalidasi',
        description:
            'Front Desk dapat memeriksa kelayakan membership dan mencatat kunjungan tanpa pengecekan manual.',
        icon: ScanLine,
    },
    {
        title: 'Pembayaran tercatat',
        description:
            'Invoice membership dan Personal Training memiliki status serta riwayat pembayaran yang jelas.',
        icon: CreditCard,
    },
    {
        title: 'Trainer & Personal Training',
        description:
            'Kelola trainer, assignment member, paket PT, jadwal sesi, kuota, dan progres pelaksanaannya.',
        icon: Dumbbell,
    },
    {
        title: 'Laporan yang relevan',
        description:
            'Owner melihat pendapatan lunas, membership, dan aktivitas gym dari angka yang siap ditindaklanjuti.',
        icon: BarChart3,
    },
    {
        title: 'Akses sesuai peran',
        description:
            'Owner, Front Desk, dan Trainer mendapat workspace sesuai tanggung jawab dengan data gym tetap terisolasi.',
        icon: ShieldCheck,
    },
];

const workflows = [
    {
        number: '01',
        title: 'Daftarkan gym',
        description:
            'Buat akun Owner, lengkapi profil gym, lalu atur paket membership yang ditawarkan.',
    },
    {
        number: '02',
        title: 'Jalankan operasional',
        description:
            'Tambahkan tim dan member, proses pembayaran, check-in, serta jadwal Personal Training.',
    },
    {
        number: '03',
        title: 'Pantau pertumbuhan',
        description:
            'Gunakan dashboard dan laporan untuk melihat aktivitas, membership, dan pendapatan gym.',
    },
];

const previewNavigation: Array<[string, boolean]> = [
    ['Ringkasan', true],
    ['Member', false],
    ['Check-in', false],
    ['Pembayaran', false],
    ['Laporan', false],
];

export default function Welcome() {
    const { auth } = usePage().props;
    const workspaceRoute = auth.isPlatformAdmin
        ? platformDashboard()
        : dashboard();

    return (
        <>
            <Head title="Kelola gym lebih rapi">
                <meta
                    name="description"
                    content="GymFlow menyatukan member, membership, pembayaran, check-in, trainer, Personal Training, dan laporan dalam satu aplikasi manajemen gym."
                />
            </Head>

            <div className="min-h-screen overflow-x-hidden bg-background text-foreground">
                <header className="sticky top-0 z-50 border-b border-border/70 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/85">
                    <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8">
                        <a
                            href="#beranda"
                            className="flex items-center gap-2.5"
                            aria-label="GymFlow, kembali ke bagian atas"
                        >
                            <span className="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm">
                                <AppLogoIcon className="size-5" aria-hidden />
                            </span>
                            <span className="text-lg font-semibold tracking-tight">
                                GymFlow
                            </span>
                        </a>

                        <nav
                            className="hidden items-center gap-7 text-sm text-muted-foreground md:flex"
                            aria-label="Navigasi utama"
                        >
                            <a
                                href="#fitur"
                                className="transition-colors hover:text-foreground"
                            >
                                Fitur
                            </a>
                            <a
                                href="#cara-kerja"
                                className="transition-colors hover:text-foreground"
                            >
                                Cara kerja
                            </a>
                            <a
                                href="#harga"
                                className="transition-colors hover:text-foreground"
                            >
                                Harga
                            </a>
                        </nav>

                        <div className="flex items-center gap-2">
                            {auth.user ? (
                                <Link
                                    href={workspaceRoute}
                                    className="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                >
                                    Buka dashboard
                                    <ArrowRight
                                        className="hidden size-4 sm:block"
                                        aria-hidden
                                    />
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="hidden h-9 items-center justify-center rounded-lg px-3 text-sm font-medium transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:inline-flex"
                                    >
                                        Masuk
                                    </Link>
                                    <Link
                                        href={register()}
                                        className="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        Mulai gratis
                                        <ArrowRight
                                            className="hidden size-4 sm:block"
                                            aria-hidden
                                        />
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                <main>
                    <section
                        id="beranda"
                        className="relative border-b border-border/70"
                    >
                        <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[0.9fr_1.1fr] lg:gap-16 lg:px-8 lg:py-28">
                            <div className="max-w-2xl">
                                <div className="inline-flex items-center gap-2 rounded-full border bg-card px-3 py-1.5 text-xs font-medium text-muted-foreground shadow-xs">
                                    <Sparkles
                                        className="size-3.5 text-primary"
                                        aria-hidden
                                    />
                                    Operasional gym dalam satu alur
                                </div>

                                <h1 className="mt-6 text-4xl leading-[1.08] font-semibold tracking-[-0.035em] text-balance sm:text-5xl lg:text-6xl">
                                    Kelola gym lebih rapi.{' '}
                                    <span className="text-primary">
                                        Layani member lebih cepat.
                                    </span>
                                </h1>

                                <p className="mt-6 max-w-xl text-base leading-7 text-pretty text-muted-foreground sm:text-lg sm:leading-8">
                                    GymFlow menyatukan member, membership,
                                    pembayaran, check-in, trainer, dan Personal
                                    Training agar tim bekerja dari data yang
                                    sama—setiap hari.
                                </p>

                                <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                    {auth.user ? (
                                        <Link
                                            href={workspaceRoute}
                                            className="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-primary px-5 text-sm font-semibold text-primary-foreground shadow-sm transition-transform hover:-translate-y-0.5 hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none motion-reduce:transform-none"
                                        >
                                            Lanjut ke dashboard
                                            <ArrowRight
                                                className="size-4"
                                                aria-hidden
                                            />
                                        </Link>
                                    ) : (
                                        <>
                                            <Link
                                                href={register()}
                                                className="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-primary px-5 text-sm font-semibold text-primary-foreground shadow-sm transition-transform hover:-translate-y-0.5 hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none motion-reduce:transform-none"
                                            >
                                                Buat gym gratis
                                                <ArrowRight
                                                    className="size-4"
                                                    aria-hidden
                                                />
                                            </Link>
                                            <Link
                                                href={login()}
                                                className="inline-flex h-11 items-center justify-center rounded-lg border bg-card px-5 text-sm font-semibold shadow-xs transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                            >
                                                Saya sudah punya akun
                                            </Link>
                                        </>
                                    )}
                                </div>

                                <div className="mt-7 flex flex-wrap gap-x-5 gap-y-2 text-xs text-muted-foreground sm:text-sm">
                                    {[
                                        'Paket Free tersedia',
                                        'Siap dipakai setelah registrasi',
                                        'Akses berbasis peran',
                                    ].map((item) => (
                                        <span
                                            key={item}
                                            className="inline-flex items-center gap-1.5"
                                        >
                                            <CheckCircle2
                                                className="size-4 text-primary"
                                                aria-hidden
                                            />
                                            {item}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            <DashboardPreview />
                        </div>
                    </section>

                    <section
                        aria-label="Ringkasan platform"
                        className="border-b"
                    >
                        <div className="mx-auto grid max-w-7xl divide-y px-4 sm:grid-cols-3 sm:divide-x sm:divide-y-0 sm:px-6 lg:px-8">
                            <Stat
                                value="1 dashboard"
                                label="untuk operasional harian"
                            />
                            <Stat
                                value="4 peran"
                                label="dengan akses yang terpisah"
                            />
                            <Stat
                                value="Data terisolasi"
                                label="untuk setiap gym"
                            />
                        </div>
                    </section>

                    <section id="fitur" className="scroll-mt-24 py-20 sm:py-24">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <SectionHeading
                                eyebrow="Fitur utama"
                                title="Semua yang dibutuhkan tim gym, tanpa alur yang terputus"
                                description="Dari member pertama masuk sampai Owner membaca laporan, setiap proses tetap saling terhubung dan mudah ditelusuri."
                            />

                            <div className="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {features.map((feature) => (
                                    <article
                                        key={feature.title}
                                        className="rounded-xl border bg-card p-6 shadow-xs transition-colors hover:border-primary/35"
                                    >
                                        <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                            <feature.icon
                                                className="size-5"
                                                aria-hidden
                                            />
                                        </span>
                                        <h3 className="mt-5 font-semibold">
                                            {feature.title}
                                        </h3>
                                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                            {feature.description}
                                        </p>
                                    </article>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="border-y bg-muted/40 py-20 sm:py-24">
                        <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-20 lg:px-8">
                            <div>
                                <p className="text-sm font-semibold text-primary">
                                    Sesuai peran, tetap satu tujuan
                                </p>
                                <h2 className="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                    Setiap orang melihat pekerjaan yang memang
                                    perlu diselesaikan
                                </h2>
                                <p className="mt-4 max-w-xl leading-7 text-muted-foreground">
                                    Tidak semua menu harus terlihat oleh semua
                                    orang. GymFlow menjaga workspace tetap fokus
                                    sekaligus mempertahankan kontrol Owner dan
                                    isolasi data gym di backend.
                                </p>

                                <div className="mt-8 grid gap-3 sm:grid-cols-2">
                                    <RoleItem
                                        icon={BadgeCheck}
                                        role="Owner"
                                        copy="Bisnis, tim, dan laporan"
                                    />
                                    <RoleItem
                                        icon={UserRoundCheck}
                                        role="Front Desk"
                                        copy="Member dan operasional"
                                    />
                                    <RoleItem
                                        icon={Dumbbell}
                                        role="Trainer"
                                        copy="Client dan jadwal sendiri"
                                    />
                                    <RoleItem
                                        icon={Fingerprint}
                                        role="Platform Admin"
                                        copy="Tenant dan paket SaaS"
                                    />
                                </div>
                            </div>

                            <div className="rounded-2xl border bg-card p-5 shadow-sm sm:p-7">
                                <div className="flex items-start justify-between gap-4 border-b pb-5">
                                    <div>
                                        <p className="text-xs font-medium text-primary">
                                            Fokus hari ini
                                        </p>
                                        <h3 className="mt-1 text-xl font-semibold">
                                            Meja Front Desk
                                        </h3>
                                    </div>
                                    <span className="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                                        Aktif
                                    </span>
                                </div>

                                <div className="grid gap-3 py-5 sm:grid-cols-2">
                                    <FocusCard
                                        icon={ScanLine}
                                        label="Check-in hari ini"
                                        value="24"
                                        note="3 baru dalam 1 jam"
                                    />
                                    <FocusCard
                                        icon={Banknote}
                                        label="Tagihan pending"
                                        value="6"
                                        note="Perlu ditindaklanjuti"
                                    />
                                </div>

                                <div className="space-y-3 border-t pt-5">
                                    <ActivityRow
                                        initials="AS"
                                        name="Andi Saputra"
                                        detail="Check-in berhasil"
                                        time="08:42"
                                    />
                                    <ActivityRow
                                        initials="RN"
                                        name="Rina Nabila"
                                        detail="Membership diperpanjang"
                                        time="08:31"
                                    />
                                    <ActivityRow
                                        initials="DP"
                                        name="Dimas Pratama"
                                        detail="Sesi PT dijadwalkan"
                                        time="08:15"
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        id="cara-kerja"
                        className="scroll-mt-24 py-20 sm:py-24"
                    >
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <SectionHeading
                                eyebrow="Cara kerja"
                                title="Mulai sederhana, berkembang bersama operasional gym"
                                description="Tidak perlu menyiapkan sistem yang rumit. Bangun fondasi data yang rapi sejak hari pertama."
                            />

                            <div className="mt-12 grid gap-4 lg:grid-cols-3">
                                {workflows.map((workflow) => (
                                    <article
                                        key={workflow.number}
                                        className="relative rounded-xl border bg-card p-6 shadow-xs"
                                    >
                                        <span className="font-mono text-sm font-semibold text-primary">
                                            {workflow.number}
                                        </span>
                                        <h3 className="mt-8 text-lg font-semibold">
                                            {workflow.title}
                                        </h3>
                                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                            {workflow.description}
                                        </p>
                                    </article>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section
                        id="harga"
                        className="scroll-mt-24 border-y bg-muted/40 py-20 sm:py-24"
                    >
                        <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:gap-20 lg:px-8">
                            <div>
                                <p className="text-sm font-semibold text-primary">
                                    Mulai tanpa beban
                                </p>
                                <h2 className="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                    Rapikan operasional gym pertama Anda secara
                                    gratis
                                </h2>
                                <p className="mt-4 max-w-xl leading-7 text-muted-foreground">
                                    Paket Free cocok untuk gym yang ingin mulai
                                    meninggalkan spreadsheet dan pencatatan
                                    terpisah. Saat kebutuhan bertambah, paket
                                    dapat dikelola tanpa memindahkan data.
                                </p>
                            </div>

                            <article className="rounded-2xl border border-primary/30 bg-card p-6 shadow-sm sm:p-8">
                                <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
                                    <div>
                                        <span className="inline-flex rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">
                                            FREE
                                        </span>
                                        <h3 className="mt-4 text-2xl font-semibold">
                                            Untuk gym yang baru mulai
                                        </h3>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            Aktif tanpa masa kedaluwarsa.
                                        </p>
                                    </div>
                                    <div className="sm:text-right">
                                        <p className="text-3xl font-semibold tracking-tight">
                                            Rp0
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            selamanya
                                        </p>
                                    </div>
                                </div>

                                <div className="my-7 border-t" />

                                <ul className="grid gap-3 text-sm sm:grid-cols-2">
                                    {[
                                        '1 gym',
                                        'Maksimal 20 member',
                                        'Maksimal 5 staff',
                                        'Fitur operasional inti',
                                    ].map((item) => (
                                        <li
                                            key={item}
                                            className="flex items-center gap-2"
                                        >
                                            <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                <Check
                                                    className="size-3.5"
                                                    aria-hidden
                                                />
                                            </span>
                                            {item}
                                        </li>
                                    ))}
                                </ul>

                                <div className="mt-8">
                                    {auth.user ? (
                                        <Link
                                            href={workspaceRoute}
                                            className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-primary px-5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none sm:w-auto"
                                        >
                                            Buka dashboard
                                            <ArrowRight
                                                className="size-4"
                                                aria-hidden
                                            />
                                        </Link>
                                    ) : (
                                        <Link
                                            href={register()}
                                            className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-primary px-5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none sm:w-auto"
                                        >
                                            Mulai dengan paket Free
                                            <ArrowRight
                                                className="size-4"
                                                aria-hidden
                                            />
                                        </Link>
                                    )}
                                </div>
                            </article>
                        </div>
                    </section>

                    <section className="py-20 sm:py-24">
                        <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                            <div className="rounded-2xl bg-foreground px-6 py-12 text-center text-background sm:px-12 sm:py-16 dark:bg-card dark:text-card-foreground dark:ring-1 dark:ring-border">
                                <span className="mx-auto flex size-11 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                                    <AppLogoIcon
                                        className="size-6"
                                        aria-hidden
                                    />
                                </span>
                                <h2 className="mx-auto mt-6 max-w-2xl text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                    Satu tempat untuk membuat tim lebih sigap
                                    dan data lebih rapi
                                </h2>
                                <p className="mx-auto mt-4 max-w-xl text-sm leading-6 text-background/70 sm:text-base dark:text-muted-foreground">
                                    Mulai dari gym pertama, lalu jalankan setiap
                                    proses dengan alur yang lebih jelas.
                                </p>

                                <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                                    {auth.user ? (
                                        <Link
                                            href={workspaceRoute}
                                            className="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-primary px-5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-background focus-visible:ring-offset-2 focus-visible:ring-offset-foreground focus-visible:outline-none"
                                        >
                                            Buka dashboard
                                            <ArrowRight
                                                className="size-4"
                                                aria-hidden
                                            />
                                        </Link>
                                    ) : (
                                        <>
                                            <Link
                                                href={register()}
                                                className="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-primary px-5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-background focus-visible:ring-offset-2 focus-visible:ring-offset-foreground focus-visible:outline-none"
                                            >
                                                Daftar sekarang
                                                <ArrowRight
                                                    className="size-4"
                                                    aria-hidden
                                                />
                                            </Link>
                                            <Link
                                                href={login()}
                                                className="inline-flex h-11 items-center justify-center rounded-lg border border-background/20 px-5 text-sm font-semibold transition-colors hover:bg-background/10 focus-visible:ring-2 focus-visible:ring-background focus-visible:outline-none dark:border-border dark:hover:bg-muted"
                                            >
                                                Masuk ke akun
                                            </Link>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t">
                    <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-8 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                        <div className="flex items-center gap-2 text-foreground">
                            <span className="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <AppLogoIcon className="size-4" aria-hidden />
                            </span>
                            <span className="font-semibold">GymFlow</span>
                        </div>
                        <p>
                            Sistem manajemen gym untuk operasional yang lebih
                            teratur.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}

function DashboardPreview() {
    return (
        <div className="relative mx-auto w-full max-w-2xl lg:mx-0">
            <div className="absolute -inset-3 -z-10 rounded-3xl bg-primary/8 sm:-inset-5" />
            <div className="overflow-hidden rounded-2xl border bg-card shadow-xl shadow-primary/10">
                <div className="flex items-center justify-between border-b px-4 py-3 sm:px-5">
                    <div className="flex items-center gap-2">
                        <span className="size-2.5 rounded-full bg-red-400" />
                        <span className="size-2.5 rounded-full bg-amber-400" />
                        <span className="size-2.5 rounded-full bg-emerald-500" />
                    </div>
                    <span className="text-[11px] font-medium text-muted-foreground">
                        gymflow / dashboard
                    </span>
                </div>

                <div className="grid min-h-[410px] grid-cols-[4.25rem_1fr] sm:grid-cols-[10.5rem_1fr]">
                    <aside className="border-r bg-muted/40 p-3 sm:p-4">
                        <div className="flex items-center gap-2">
                            <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <AppLogoIcon className="size-4" aria-hidden />
                            </span>
                            <span className="hidden text-sm font-semibold sm:block">
                                GymFlow
                            </span>
                        </div>
                        <div className="mt-8 space-y-2">
                            {previewNavigation.map(([label, active]) => (
                                <div
                                    key={label}
                                    className={`flex h-8 items-center gap-2 rounded-md px-2 text-xs ${
                                        active
                                            ? 'bg-primary/10 font-medium text-primary'
                                            : 'text-muted-foreground'
                                    }`}
                                >
                                    <span
                                        className={`size-2 rounded-full ${active ? 'bg-primary' : 'bg-border'}`}
                                    />
                                    <span className="hidden sm:block">
                                        {label}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </aside>

                    <div className="min-w-0 p-4 sm:p-6">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-[10px] font-medium text-primary sm:text-xs">
                                    Atlas Fitness · Owner
                                </p>
                                <p className="mt-1 text-base font-semibold sm:text-lg">
                                    Ringkasan bisnis
                                </p>
                            </div>
                            <span className="rounded-md border px-2 py-1 text-[9px] text-muted-foreground sm:text-[10px]">
                                Hari ini
                            </span>
                        </div>

                        <div className="mt-5 grid gap-3 sm:grid-cols-3">
                            <PreviewMetric
                                label="Member aktif"
                                value="186"
                                icon={UsersRound}
                            />
                            <PreviewMetric
                                label="Check-in"
                                value="24"
                                icon={ScanLine}
                            />
                            <PreviewMetric
                                label="Akan berakhir"
                                value="8"
                                icon={Clock3}
                            />
                        </div>

                        <div className="mt-3 grid gap-3 sm:grid-cols-[1.25fr_0.75fr]">
                            <div className="rounded-lg border p-4">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-[10px] text-muted-foreground sm:text-xs">
                                            Pendapatan bulan ini
                                        </p>
                                        <p className="mt-1 text-lg font-semibold tabular-nums sm:text-xl">
                                            Rp18,6 jt
                                        </p>
                                    </div>
                                    <BarChart3
                                        className="size-4 text-primary"
                                        aria-hidden
                                    />
                                </div>
                                <svg
                                    viewBox="0 0 320 92"
                                    className="mt-4 h-20 w-full"
                                    role="img"
                                    aria-label="Grafik pendapatan meningkat dalam tujuh hari terakhir"
                                >
                                    <path
                                        d="M4 77 C42 69, 55 74, 82 55 S132 67, 160 43 S210 50, 234 29 S277 38, 316 10"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="3"
                                        strokeLinecap="round"
                                        className="text-primary"
                                    />
                                    <path
                                        d="M4 77 C42 69, 55 74, 82 55 S132 67, 160 43 S210 50, 234 29 S277 38, 316 10"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="10"
                                        strokeLinecap="round"
                                        className="text-primary/10"
                                    />
                                </svg>
                            </div>

                            <div className="rounded-lg border p-4">
                                <p className="text-[10px] font-medium sm:text-xs">
                                    Aktivitas terbaru
                                </p>
                                <div className="mt-4 space-y-4">
                                    <MiniActivity
                                        label="Check-in berhasil"
                                        time="08:42"
                                    />
                                    <MiniActivity
                                        label="Pembayaran lunas"
                                        time="08:31"
                                    />
                                    <MiniActivity
                                        label="Member baru"
                                        time="08:15"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function PreviewMetric({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value: string;
    icon: Feature['icon'];
}) {
    return (
        <div className="rounded-lg border p-3">
            <div className="flex items-center justify-between gap-2">
                <span className="text-[9px] text-muted-foreground sm:text-[10px]">
                    {label}
                </span>
                <Icon className="size-3.5 text-primary" aria-hidden />
            </div>
            <p className="mt-2 text-lg font-semibold tabular-nums sm:text-xl">
                {value}
            </p>
        </div>
    );
}

function MiniActivity({ label, time }: { label: string; time: string }) {
    return (
        <div className="flex items-start gap-2">
            <span className="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                <Check className="size-2.5" aria-hidden />
            </span>
            <div className="min-w-0">
                <p className="truncate text-[9px] font-medium sm:text-[10px]">
                    {label}
                </p>
                <p className="text-[8px] text-muted-foreground sm:text-[9px]">
                    {time}
                </p>
            </div>
        </div>
    );
}

function Stat({ value, label }: { value: string; label: string }) {
    return (
        <div className="flex items-baseline gap-2 px-2 py-6 sm:flex-col sm:items-center sm:gap-1 sm:px-6 sm:text-center">
            <p className="font-semibold">{value}</p>
            <p className="text-sm text-muted-foreground">{label}</p>
        </div>
    );
}

function SectionHeading({
    eyebrow,
    title,
    description,
}: {
    eyebrow: string;
    title: string;
    description: string;
}) {
    return (
        <div className="max-w-2xl">
            <p className="text-sm font-semibold text-primary">{eyebrow}</p>
            <h2 className="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                {title}
            </h2>
            <p className="mt-4 leading-7 text-muted-foreground">
                {description}
            </p>
        </div>
    );
}

function RoleItem({
    icon: Icon,
    role,
    copy,
}: {
    icon: Feature['icon'];
    role: string;
    copy: string;
}) {
    return (
        <div className="flex items-center gap-3 rounded-lg border bg-card p-3 shadow-xs">
            <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <Icon className="size-4.5" aria-hidden />
            </span>
            <div className="min-w-0">
                <p className="text-sm font-semibold">{role}</p>
                <p className="truncate text-xs text-muted-foreground">{copy}</p>
            </div>
        </div>
    );
}

function FocusCard({
    icon: Icon,
    label,
    value,
    note,
}: {
    icon: Feature['icon'];
    label: string;
    value: string;
    note: string;
}) {
    return (
        <div className="rounded-lg border bg-background p-4">
            <div className="flex items-center justify-between gap-3">
                <p className="text-xs text-muted-foreground">{label}</p>
                <Icon className="size-4 text-primary" aria-hidden />
            </div>
            <p className="mt-3 text-2xl font-semibold tabular-nums">{value}</p>
            <p className="mt-1 text-[11px] text-muted-foreground">{note}</p>
        </div>
    );
}

function ActivityRow({
    initials,
    name,
    detail,
    time,
}: {
    initials: string;
    name: string;
    detail: string;
    time: string;
}) {
    return (
        <div className="flex items-center gap-3">
            <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                {initials}
            </span>
            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">{name}</p>
                <p className="truncate text-xs text-muted-foreground">
                    {detail}
                </p>
            </div>
            <span className="text-xs text-muted-foreground">{time}</span>
        </div>
    );
}
