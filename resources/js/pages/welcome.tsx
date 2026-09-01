import { Head, Link, usePage } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    Check,
    ChevronRight,
    CircleCheck,
    Clock3,
    CreditCard,
    Dumbbell,
    Menu,
    ScanLine,
    ShieldCheck,
    UserRoundCheck,
    UsersRound,
} from 'lucide-react';
import type { ComponentType } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { dashboard, login, register } from '@/routes';
import { dashboard as platformDashboard } from '@/routes/platform';

type Icon = ComponentType<{
    className?: string;
    'aria-hidden'?: boolean;
}>;

type Feature = {
    number: string;
    title: string;
    description: string;
    icon: Icon;
};

const features: Feature[] = [
    {
        number: '01',
        title: 'Member & membership',
        description:
            'Profil member, paket aktif, masa berlaku, dan riwayat perpanjangan tetap terhubung.',
        icon: UsersRound,
    },
    {
        number: '02',
        title: 'Check-in tervalidasi',
        description:
            'Front Desk dapat memeriksa kelayakan membership sebelum kunjungan tercatat.',
        icon: ScanLine,
    },
    {
        number: '03',
        title: 'Pembayaran yang jelas',
        description:
            'Invoice membership dan Personal Training memiliki status serta jejak pembayaran.',
        icon: CreditCard,
    },
    {
        number: '04',
        title: 'Trainer & PT',
        description:
            'Assignment member, paket PT, jadwal, kuota, dan catatan sesi ada dalam satu alur.',
        icon: Dumbbell,
    },
    {
        number: '05',
        title: 'Laporan relevan',
        description:
            'Owner melihat angka operasional yang ringkas dan siap ditindaklanjuti.',
        icon: BarChart3,
    },
    {
        number: '06',
        title: 'Akses sesuai peran',
        description:
            'Setiap tim mendapat ruang kerja yang fokus dengan isolasi data gym di backend.',
        icon: ShieldCheck,
    },
];

const roles = [
    {
        role: 'Owner',
        description: 'Bisnis, tim, konfigurasi, dan laporan.',
        icon: BarChart3,
    },
    {
        role: 'Front Desk',
        description: 'Member, transaksi, dan operasional harian.',
        icon: UserRoundCheck,
    },
    {
        role: 'Trainer',
        description: 'Member binaan, jadwal, dan sesi sendiri.',
        icon: Dumbbell,
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
    const { auth, name } = usePage().props;
    const workspaceRoute = auth.isPlatformAdmin
        ? platformDashboard()
        : dashboard();

    return (
        <>
            <Head title="Operasional gym, dalam satu alur">
                <meta
                    name="description"
                    content={`${name} menyatukan member, membership, pembayaran, check-in, trainer, Personal Training, dan laporan dalam satu aplikasi manajemen gym.`}
                />
            </Head>

            <div className="min-h-screen overflow-x-clip bg-background text-foreground selection:bg-primary/20">
                <header className="sticky top-0 z-50 border-b border-border/70 bg-background/95 backdrop-blur-md supports-[backdrop-filter]:bg-background/85">
                    <div className="mx-auto flex h-16 max-w-[90rem] items-center justify-between gap-4 px-4 sm:px-8 lg:px-12">
                        <a
                            href="#beranda"
                            className="flex shrink-0 items-center gap-2.5 rounded-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:outline-none"
                            aria-label={`${name}, kembali ke bagian atas`}
                        >
                            <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                <AppLogoIcon className="size-4.5" aria-hidden />
                            </span>
                            <span className="text-base font-semibold tracking-[-0.02em]">
                                {name}
                            </span>
                        </a>

                        <nav
                            className="hidden items-center gap-0.5 rounded-full border bg-muted/25 p-1 text-sm text-muted-foreground lg:flex"
                            aria-label="Navigasi utama"
                        >
                            <a
                                href="#platform"
                                className="rounded-full px-3.5 py-1.5 font-medium transition-colors hover:bg-background hover:text-foreground"
                            >
                                Platform
                            </a>
                            <a
                                href="#fitur"
                                className="rounded-full px-3.5 py-1.5 font-medium transition-colors hover:bg-background hover:text-foreground"
                            >
                                Fitur
                            </a>
                            <a
                                href="#cara-kerja"
                                className="rounded-full px-3.5 py-1.5 font-medium transition-colors hover:bg-background hover:text-foreground"
                            >
                                Cara kerja
                            </a>
                            <a
                                href="#harga"
                                className="rounded-full px-3.5 py-1.5 font-medium transition-colors hover:bg-background hover:text-foreground"
                            >
                                Harga
                            </a>
                        </nav>

                        <div className="hidden items-center gap-2 lg:flex">
                            {auth.user ? (
                                <Link
                                    href={workspaceRoute}
                                    prefetch
                                    className="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
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
                                        href={login()}
                                        prefetch
                                        className="inline-flex h-9 items-center justify-center rounded-md px-3.5 text-sm font-medium transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                    >
                                        Masuk
                                    </Link>
                                    <Link
                                        href={register()}
                                        prefetch
                                        className="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        Daftar
                                        <ArrowRight
                                            className="hidden size-4 sm:block"
                                            aria-hidden
                                        />
                                    </Link>
                                </>
                            )}
                        </div>

                        <div className="flex items-center gap-2 lg:hidden">
                            <Link
                                href={auth.user ? workspaceRoute : login()}
                                prefetch
                                className="inline-flex h-9 items-center justify-center rounded-md bg-primary px-3 text-xs font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none sm:px-4 sm:text-sm"
                            >
                                {auth.user ? 'Dashboard' : 'Masuk'}
                            </Link>

                            <Sheet>
                                <SheetTrigger asChild>
                                    <button
                                        type="button"
                                        className="inline-flex size-9 items-center justify-center rounded-md border bg-background text-foreground transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                        aria-label="Buka menu navigasi"
                                    >
                                        <Menu
                                            className="size-4.5"
                                            aria-hidden
                                        />
                                    </button>
                                </SheetTrigger>
                                <SheetContent className="w-[88vw] max-w-sm gap-0 border-l p-0 shadow-none [&>button]:top-5 [&>button]:right-5">
                                    <SheetHeader className="border-b px-5 py-5 text-left">
                                        <SheetTitle className="flex items-center gap-2.5">
                                            <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                                <AppLogoIcon
                                                    className="size-4.5"
                                                    aria-hidden
                                                />
                                            </span>
                                            <span className="tracking-[-0.02em]">
                                                {name}
                                            </span>
                                        </SheetTitle>
                                        <SheetDescription className="max-w-[15rem] text-xs leading-5">
                                            Operasional gym yang lebih terarah,
                                            dari satu workspace.
                                        </SheetDescription>
                                    </SheetHeader>

                                    <nav
                                        className="flex flex-1 flex-col px-5 py-6"
                                        aria-label="Navigasi mobile"
                                    >
                                        <p className="mb-3 text-[10px] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                            Jelajahi
                                        </p>
                                        {[
                                            ['Platform', '#platform'],
                                            ['Fitur', '#fitur'],
                                            ['Cara kerja', '#cara-kerja'],
                                            ['Harga', '#harga'],
                                        ].map(([label, href]) => (
                                            <SheetClose key={href} asChild>
                                                <a
                                                    href={href}
                                                    className="group flex items-center justify-between border-b py-4 text-base font-medium tracking-[-0.01em] transition-colors last:border-b-0 hover:text-primary"
                                                >
                                                    {label}
                                                    <ChevronRight
                                                        className="size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-primary"
                                                        aria-hidden
                                                    />
                                                </a>
                                            </SheetClose>
                                        ))}
                                    </nav>

                                    <SheetFooter className="border-t p-5">
                                        {auth.user ? (
                                            <SheetClose asChild>
                                                <Link
                                                    href={workspaceRoute}
                                                    prefetch
                                                    className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                                                >
                                                    Buka dashboard
                                                    <ArrowRight
                                                        className="size-4"
                                                        aria-hidden
                                                    />
                                                </Link>
                                            </SheetClose>
                                        ) : (
                                            <>
                                                <SheetClose asChild>
                                                    <Link
                                                        href={register()}
                                                        prefetch
                                                        className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                                                    >
                                                        Daftar
                                                        <ArrowRight
                                                            className="size-4"
                                                            aria-hidden
                                                        />
                                                    </Link>
                                                </SheetClose>
                                                <SheetClose asChild>
                                                    <Link
                                                        href={login()}
                                                        prefetch
                                                        className="inline-flex h-11 w-full items-center justify-center rounded-md border px-4 text-sm font-semibold transition-colors hover:bg-muted"
                                                    >
                                                        Masuk ke akun
                                                    </Link>
                                                </SheetClose>
                                            </>
                                        )}
                                    </SheetFooter>
                                </SheetContent>
                            </Sheet>
                        </div>
                    </div>
                </header>

                <main>
                    <section
                        id="beranda"
                        className="relative border-b border-border/70"
                    >
                        <div className="absolute inset-x-0 top-0 -z-10 h-3/4 bg-muted/35" />
                        <div className="mx-auto max-w-[90rem] px-5 pt-16 pb-10 sm:px-8 sm:pt-24 lg:px-12 lg:pt-28 lg:pb-14">
                            <div className="grid items-end gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(22rem,0.52fr)] lg:gap-20">
                                <div className="max-w-5xl">
                                    <p className="mb-6 flex items-center gap-3 text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                                        <span className="h-px w-8 bg-primary" />
                                        Sistem manajemen gym
                                    </p>
                                    <h1 className="max-w-5xl text-[clamp(3rem,7vw,7.1rem)] leading-[0.94] font-semibold tracking-[-0.065em] text-balance">
                                        Operasional rapi.
                                        <span className="mt-1 block text-muted-foreground/75">
                                            Pertumbuhan terukur.
                                        </span>
                                    </h1>
                                </div>

                                <div className="pb-1 lg:pb-3">
                                    <p className="max-w-lg text-base leading-7 text-pretty text-muted-foreground sm:text-lg sm:leading-8">
                                        Satu workspace untuk member, pembayaran,
                                        check-in, trainer, Personal Training,
                                        dan laporan—dibuat agar tim bergerak
                                        lebih cepat tanpa kehilangan kontrol.
                                    </p>

                                    <div className="mt-7 flex flex-col gap-3 sm:flex-row lg:flex-col xl:flex-row">
                                        {auth.user ? (
                                            <PrimaryLink href={workspaceRoute}>
                                                Lanjut ke dashboard
                                            </PrimaryLink>
                                        ) : (
                                            <>
                                                <PrimaryLink href={register()}>
                                                    Buat workspace gratis
                                                </PrimaryLink>
                                                <SecondaryLink href={login()}>
                                                    Lihat akun saya
                                                </SecondaryLink>
                                            </>
                                        )}
                                    </div>

                                    <p className="mt-5 flex items-center gap-2 text-xs text-muted-foreground">
                                        <CircleCheck
                                            className="size-4 text-primary"
                                            aria-hidden
                                        />
                                        Paket Free aktif otomatis. Tanpa pilih
                                        paket di awal.
                                    </p>
                                </div>
                            </div>

                            <div
                                id="platform"
                                className="scroll-mt-28 pt-16 lg:pt-20"
                            >
                                <DashboardPreview />
                            </div>
                        </div>
                    </section>

                    <section
                        aria-label={`Ringkasan ${name}`}
                        className="border-b"
                    >
                        <div className="mx-auto grid max-w-[90rem] divide-y px-5 sm:grid-cols-3 sm:divide-x sm:divide-y-0 sm:px-8 lg:px-12">
                            <Stat
                                value="01"
                                label="Workspace untuk operasional"
                            />
                            <Stat
                                value="03"
                                label="Peran dengan fokus berbeda"
                            />
                            <Stat value="100%" label="Data gym terisolasi" />
                        </div>
                    </section>

                    <section
                        id="fitur"
                        className="scroll-mt-24 py-20 sm:py-28 lg:py-32"
                    >
                        <div className="mx-auto max-w-[90rem] px-5 sm:px-8 lg:px-12">
                            <div className="grid gap-8 lg:grid-cols-[0.68fr_1.32fr] lg:gap-20">
                                <SectionHeading
                                    eyebrow="Satu sumber data"
                                    title="Lebih sedikit pindah aplikasi. Lebih banyak pekerjaan selesai."
                                    description="Setiap modul dirancang sebagai satu rangkaian kerja, bukan sekadar kumpulan menu."
                                />

                                <div className="border-t">
                                    {features.map((feature) => (
                                        <FeatureRow
                                            key={feature.number}
                                            feature={feature}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="border-y bg-foreground text-background">
                        <div className="mx-auto grid max-w-[90rem] lg:grid-cols-[0.85fr_1.15fr]">
                            <div className="flex flex-col justify-between gap-16 border-background/15 px-5 py-16 sm:px-8 sm:py-20 lg:border-r lg:px-12 lg:py-24">
                                <div>
                                    <p className="text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                                        Dibuat untuk tim nyata
                                    </p>
                                    <h2 className="mt-5 max-w-2xl text-3xl leading-tight font-semibold tracking-[-0.04em] text-balance sm:text-5xl">
                                        Setiap peran melihat yang penting untuk
                                        hari ini.
                                    </h2>
                                </div>
                                <p className="max-w-lg text-sm leading-7 text-background/65 sm:text-base dark:text-muted-foreground">
                                    Navigasi tetap fokus, tanggung jawab lebih
                                    jelas, dan kontrol bisnis tetap berada di
                                    tangan Owner.
                                </p>
                            </div>

                            <div className="divide-y divide-background/15 border-t border-background/15 lg:border-t-0">
                                {roles.map((item, index) => (
                                    <RoleRow
                                        key={item.role}
                                        index={index + 1}
                                        {...item}
                                    />
                                ))}
                            </div>
                        </div>
                    </section>

                    <section
                        id="cara-kerja"
                        className="scroll-mt-24 py-20 sm:py-28 lg:py-32"
                    >
                        <div className="mx-auto max-w-[90rem] px-5 sm:px-8 lg:px-12">
                            <SectionHeading
                                eyebrow="Mulai tanpa rumit"
                                title="Dari gym pertama ke operasional harian."
                                description="Tiga langkah yang membawa tim dari setup awal ke data yang siap dibaca."
                            />

                            <ol className="mt-12 grid border-y md:grid-cols-3 md:divide-x">
                                <ProcessStep
                                    number="01"
                                    title="Buat workspace"
                                    description="Daftarkan akun Owner dan nama gym. Paket Free langsung aktif."
                                />
                                <ProcessStep
                                    number="02"
                                    title="Siapkan operasional"
                                    description="Tambahkan paket, tim, dan member dengan akses yang sesuai."
                                />
                                <ProcessStep
                                    number="03"
                                    title="Jalankan & ukur"
                                    description="Proses transaksi, check-in, sesi PT, lalu baca laporannya."
                                />
                            </ol>
                        </div>
                    </section>

                    <section
                        id="harga"
                        className="scroll-mt-24 border-y bg-muted/30"
                    >
                        <div className="mx-auto grid max-w-[90rem] lg:grid-cols-[0.85fr_1.15fr]">
                            <div className="px-5 py-16 sm:px-8 sm:py-20 lg:px-12 lg:py-24">
                                <p className="text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                                    Mulai dari Free
                                </p>
                                <h2 className="mt-5 max-w-xl text-4xl leading-tight font-semibold tracking-[-0.045em] text-balance sm:text-5xl">
                                    Validasi alur kerja sebelum bertumbuh.
                                </h2>
                                <p className="mt-5 max-w-lg leading-7 text-muted-foreground">
                                    Registrasi tidak meminta kartu atau pilihan
                                    paket. Workspace pertama langsung memakai
                                    paket Free.
                                </p>
                            </div>

                            <div className="border-t border-border bg-background px-5 py-16 sm:px-8 sm:py-20 lg:border-t-0 lg:border-l lg:px-12 lg:py-24">
                                <div className="flex items-end justify-between gap-6 border-b pb-8">
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">
                                            Paket Free
                                        </p>
                                        <p className="mt-2 text-5xl font-semibold tracking-[-0.055em]">
                                            Rp0
                                        </p>
                                    </div>
                                    <span className="rounded-full border border-primary/30 bg-primary/5 px-3 py-1.5 text-xs font-semibold text-primary">
                                        Aktif otomatis
                                    </span>
                                </div>

                                <div className="grid gap-x-8 gap-y-4 py-8 sm:grid-cols-2">
                                    <PlanItem label="1 gym" />
                                    <PlanItem label="Hingga 20 member" />
                                    <PlanItem label="Hingga 5 staf" />
                                    <PlanItem label="Fitur operasional inti" />
                                </div>

                                {auth.user ? (
                                    <PrimaryLink href={workspaceRoute}>
                                        Buka dashboard
                                    </PrimaryLink>
                                ) : (
                                    <PrimaryLink href={register()}>
                                        Mulai dengan Free
                                    </PrimaryLink>
                                )}
                            </div>
                        </div>
                    </section>

                    <section className="py-20 sm:py-28">
                        <div className="mx-auto max-w-[90rem] px-5 sm:px-8 lg:px-12">
                            <div className="grid items-end gap-10 border-b border-foreground pb-10 sm:pb-14 lg:grid-cols-[1fr_auto]">
                                <div>
                                    <p className="text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                                        Siap bekerja lebih rapi?
                                    </p>
                                    <h2 className="mt-5 max-w-4xl text-4xl leading-[1.05] font-semibold tracking-[-0.05em] text-balance sm:text-6xl">
                                        Jadikan setiap hari operasional lebih
                                        mudah dikendalikan.
                                    </h2>
                                </div>

                                {auth.user ? (
                                    <PrimaryLink href={workspaceRoute}>
                                        Buka dashboard
                                    </PrimaryLink>
                                ) : (
                                    <PrimaryLink href={register()}>
                                        Buat workspace
                                    </PrimaryLink>
                                )}
                            </div>
                        </div>
                    </section>
                </main>

                <footer>
                    <div className="mx-auto flex max-w-[90rem] flex-col gap-5 px-5 pb-10 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12">
                        <div className="flex items-center gap-2.5 text-foreground">
                            <span className="flex size-8 items-center justify-center rounded-md bg-foreground text-background">
                                <AppLogoIcon className="size-4" aria-hidden />
                            </span>
                            <span className="font-semibold">{name}</span>
                        </div>
                        <p>
                            Sistem operasional untuk gym yang ingin bertumbuh.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}

function PrimaryLink({
    href,
    children,
}: {
    href: NonNullable<InertiaLinkProps['href']>;
    children: string;
}) {
    return (
        <Link
            href={href}
            prefetch
            className="inline-flex h-12 items-center justify-center gap-3 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
        >
            {children}
            <ArrowRight className="size-4" aria-hidden />
        </Link>
    );
}

function SecondaryLink({
    href,
    children,
}: {
    href: NonNullable<InertiaLinkProps['href']>;
    children: string;
}) {
    return (
        <Link
            href={href}
            prefetch
            className="inline-flex h-12 items-center justify-center rounded-md border border-border bg-background px-5 text-sm font-semibold transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            {children}
        </Link>
    );
}

function DashboardPreview() {
    return (
        <div className="overflow-hidden rounded-xl border border-border bg-card">
            <div className="flex h-12 items-center justify-between border-b px-4 sm:px-6">
                <div className="flex items-center gap-3">
                    <span className="size-2 rounded-full bg-primary" />
                    <span className="text-xs font-medium">Workspace Owner</span>
                </div>
                <span className="text-[11px] text-muted-foreground">
                    gymlo / dashboard
                </span>
            </div>

            <div className="grid min-h-[28rem] grid-cols-[4.25rem_1fr] sm:grid-cols-[12rem_1fr]">
                <aside className="border-r bg-muted/25 p-3 sm:p-5">
                    <div className="flex items-center gap-2.5">
                        <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-foreground text-background">
                            <AppLogoIcon className="size-4" aria-hidden />
                        </span>
                        <span className="hidden text-sm font-semibold sm:block">
                            Atlas Club
                        </span>
                    </div>
                    <div className="mt-8 space-y-1.5">
                        {previewNavigation.map(([label, active]) => (
                            <div
                                key={label}
                                className={`flex h-9 items-center gap-2.5 rounded-md px-2.5 text-xs ${
                                    active
                                        ? 'bg-foreground font-medium text-background'
                                        : 'text-muted-foreground'
                                }`}
                            >
                                <span
                                    className={`size-1.5 rounded-full ${active ? 'bg-primary' : 'bg-border'}`}
                                />
                                <span className="hidden sm:block">{label}</span>
                            </div>
                        ))}
                    </div>
                </aside>

                <div className="min-w-0 p-4 sm:p-7 lg:p-9">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-xs font-medium text-primary">
                                Selasa, 1 September
                            </p>
                            <h2 className="mt-1 text-lg font-semibold tracking-tight sm:text-2xl">
                                Ringkasan bisnis
                            </h2>
                        </div>
                        <span className="hidden rounded-md border px-3 py-1.5 text-[11px] text-muted-foreground sm:block">
                            Hari ini
                        </span>
                    </div>

                    <div className="mt-6 grid gap-3 sm:grid-cols-3">
                        <PreviewMetric
                            label="Member aktif"
                            value="186"
                            note="+12 bulan ini"
                            icon={UsersRound}
                        />
                        <PreviewMetric
                            label="Check-in"
                            value="24"
                            note="Hari ini"
                            icon={ScanLine}
                        />
                        <PreviewMetric
                            label="Akan berakhir"
                            value="8"
                            note="7 hari ke depan"
                            icon={Clock3}
                        />
                    </div>

                    <div className="mt-3 grid gap-3 lg:grid-cols-[1.3fr_0.7fr]">
                        <div className="rounded-lg border p-4 sm:p-5">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Pendapatan bulan ini
                                    </p>
                                    <p className="mt-1.5 text-xl font-semibold tracking-tight tabular-nums sm:text-2xl">
                                        Rp18,6 juta
                                    </p>
                                </div>
                                <span className="rounded-full bg-primary/10 px-2.5 py-1 text-[10px] font-semibold text-primary">
                                    +14,2%
                                </span>
                            </div>
                            <svg
                                viewBox="0 0 420 112"
                                className="mt-5 h-24 w-full"
                                role="img"
                                aria-label="Grafik pendapatan meningkat dalam tujuh hari terakhir"
                            >
                                <path
                                    d="M4 96 C54 88, 78 95, 111 70 S176 82, 218 51 S281 66, 320 35 S376 42, 416 10"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="3"
                                    strokeLinecap="round"
                                    className="text-primary"
                                />
                                <path
                                    d="M4 108 H416"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="1"
                                    className="text-border"
                                />
                            </svg>
                        </div>

                        <div className="rounded-lg border p-4 sm:p-5">
                            <p className="text-xs font-semibold">
                                Aktivitas terbaru
                            </p>
                            <div className="mt-5 space-y-5">
                                <Activity
                                    label="Check-in berhasil"
                                    time="08:42"
                                />
                                <Activity
                                    label="Pembayaran lunas"
                                    time="08:31"
                                />
                                <Activity label="Member baru" time="08:15" />
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
    note,
    icon: Icon,
}: {
    label: string;
    value: string;
    note: string;
    icon: Icon;
}) {
    return (
        <div className="rounded-lg border p-3.5 sm:p-4">
            <div className="flex items-center justify-between gap-2">
                <span className="text-[10px] text-muted-foreground sm:text-xs">
                    {label}
                </span>
                <Icon className="size-4 text-primary" aria-hidden />
            </div>
            <p className="mt-3 text-xl font-semibold tracking-tight tabular-nums sm:text-2xl">
                {value}
            </p>
            <p className="mt-1 hidden text-[10px] text-muted-foreground sm:block">
                {note}
            </p>
        </div>
    );
}

function Activity({ label, time }: { label: string; time: string }) {
    return (
        <div className="flex items-center gap-2.5">
            <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                <Check className="size-3" aria-hidden />
            </span>
            <div className="min-w-0 flex-1">
                <p className="truncate text-[10px] font-medium sm:text-xs">
                    {label}
                </p>
            </div>
            <span className="text-[9px] text-muted-foreground sm:text-[10px]">
                {time}
            </span>
        </div>
    );
}

function Stat({ value, label }: { value: string; label: string }) {
    return (
        <div className="flex items-center gap-4 py-7 sm:flex-col sm:items-start sm:px-8 sm:py-9 first:sm:pl-0 last:sm:pr-0">
            <p className="text-3xl font-semibold tracking-[-0.04em] tabular-nums">
                {value}
            </p>
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
            <p className="text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                {eyebrow}
            </p>
            <h2 className="mt-5 text-3xl leading-tight font-semibold tracking-[-0.04em] text-balance sm:text-5xl">
                {title}
            </h2>
            <p className="mt-5 max-w-xl leading-7 text-muted-foreground">
                {description}
            </p>
        </div>
    );
}

function FeatureRow({ feature }: { feature: Feature }) {
    return (
        <article className="group grid gap-4 border-b py-6 sm:grid-cols-[3rem_3rem_1fr_auto] sm:items-center sm:gap-5 sm:py-7">
            <span className="hidden text-xs font-medium text-muted-foreground sm:block">
                {feature.number}
            </span>
            <span className="flex size-10 items-center justify-center rounded-md border text-primary transition-colors group-hover:border-primary/40 group-hover:bg-primary/5">
                <feature.icon className="size-4.5" aria-hidden />
            </span>
            <div>
                <h3 className="font-semibold tracking-tight">
                    {feature.title}
                </h3>
                <p className="mt-1.5 max-w-2xl text-sm leading-6 text-muted-foreground">
                    {feature.description}
                </p>
            </div>
            <ChevronRight
                className="hidden size-4 text-muted-foreground transition-transform group-hover:translate-x-1 sm:block"
                aria-hidden
            />
        </article>
    );
}

function RoleRow({
    index,
    role,
    description,
    icon: Icon,
}: {
    index: number;
    role: string;
    description: string;
    icon: Icon;
}) {
    return (
        <article className="grid grid-cols-[auto_1fr] items-start gap-5 px-5 py-8 sm:grid-cols-[3rem_3.5rem_1fr] sm:items-center sm:px-8 lg:px-12 lg:py-10">
            <span className="hidden text-xs text-background/45 sm:block">
                0{index}
            </span>
            <span className="flex size-11 items-center justify-center rounded-md border border-background/20 text-primary sm:size-12">
                <Icon className="size-5" aria-hidden />
            </span>
            <div>
                <h3 className="text-lg font-semibold">{role}</h3>
                <p className="mt-1 text-sm leading-6 text-background/60 dark:text-muted-foreground">
                    {description}
                </p>
            </div>
        </article>
    );
}

function ProcessStep({
    number,
    title,
    description,
}: {
    number: string;
    title: string;
    description: string;
}) {
    return (
        <li className="grid grid-cols-[3rem_1fr] gap-4 py-8 md:block md:px-8 md:py-10 first:md:pl-0 last:md:pr-0">
            <span className="text-sm font-semibold text-primary">{number}</span>
            <div className="md:mt-14">
                <h3 className="text-xl font-semibold tracking-tight">
                    {title}
                </h3>
                <p className="mt-3 max-w-sm text-sm leading-6 text-muted-foreground">
                    {description}
                </p>
            </div>
        </li>
    );
}

function PlanItem({ label }: { label: string }) {
    return (
        <span className="flex items-center gap-2.5 text-sm">
            <span className="flex size-5 items-center justify-center rounded-full bg-primary/10 text-primary">
                <Check className="size-3" aria-hidden />
            </span>
            {label}
        </span>
    );
}
