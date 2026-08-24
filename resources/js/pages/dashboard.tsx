import { Head, usePage } from '@inertiajs/react';
import {
    Activity,
    BadgeCheck,
    CalendarClock,
    CircleDollarSign,
    Clock3,
    Users,
    UserRoundCheck,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';

const operationalStats = [
    {
        label: 'Member aktif',
        value: '0',
        detail: 'Belum ada data member',
        icon: Users,
    },
    {
        label: 'Check-in hari ini',
        value: '0',
        detail: 'Belum ada kunjungan',
        icon: UserRoundCheck,
    },
    {
        label: 'Segera berakhir',
        value: '0',
        detail: 'Dalam 7 hari ke depan',
        icon: CalendarClock,
    },
    {
        label: 'Pendapatan hari ini',
        value: 'Rp0',
        detail: 'Belum ada pembayaran',
        icon: CircleDollarSign,
    },
];

export default function Dashboard() {
    const { auth } = usePage().props;
    const gym = auth.currentGym;

    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div className="min-w-0">
                        <p className="text-sm font-medium text-primary">
                            {gym?.name}
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal text-foreground">
                            Dashboard
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Ringkasan operasional gym hari ini.
                        </p>
                    </div>
                    <Badge
                        variant="outline"
                        className="w-fit gap-2 border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        <span className="size-1.5 rounded-full bg-emerald-500" />
                        Workspace aktif
                    </Badge>
                </div>

                <section aria-labelledby="operational-summary-heading">
                    <div className="mb-4">
                        <h2
                            id="operational-summary-heading"
                            className="text-base font-semibold"
                        >
                            Operasional hari ini
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Data utama yang perlu dipantau front desk.
                        </p>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        {operationalStats.map((stat) => (
                            <div
                                key={stat.label}
                                className="rounded-lg border bg-card p-4"
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div className="min-w-0">
                                        <p className="text-sm text-muted-foreground">
                                            {stat.label}
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {stat.value}
                                        </p>
                                    </div>
                                    <div className="flex size-9 shrink-0 items-center justify-center rounded-md bg-accent text-primary">
                                        <stat.icon
                                            className="size-4.5"
                                            aria-hidden="true"
                                        />
                                    </div>
                                </div>
                                <p className="mt-3 text-xs text-muted-foreground">
                                    {stat.detail}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>

                <div className="grid gap-8 border-t pt-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <section aria-labelledby="recent-activity-heading">
                        <div className="mb-4">
                            <h2
                                id="recent-activity-heading"
                                className="text-base font-semibold"
                            >
                                Aktivitas terbaru
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Check-in dan pembayaran terbaru akan tampil di
                                sini.
                            </p>
                        </div>
                        <div className="flex min-h-52 flex-col items-center justify-center rounded-lg border border-dashed bg-card px-6 py-10 text-center">
                            <div className="flex size-10 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                <Activity
                                    className="size-5"
                                    aria-hidden="true"
                                />
                            </div>
                            <p className="mt-4 text-sm font-medium">
                                Belum ada aktivitas hari ini
                            </p>
                            <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                                Aktivitas operasional pertama akan tercatat
                                otomatis.
                            </p>
                        </div>
                    </section>

                    <aside aria-labelledby="workspace-heading">
                        <h2
                            id="workspace-heading"
                            className="text-base font-semibold"
                        >
                            Workspace
                        </h2>
                        <dl className="mt-4 divide-y border-y text-sm">
                            <div className="flex items-center justify-between gap-4 py-3">
                                <dt className="flex items-center gap-2 text-muted-foreground">
                                    <BadgeCheck
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Status
                                </dt>
                                <dd className="font-medium text-emerald-700 dark:text-emerald-400">
                                    Aktif
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4 py-3">
                                <dt className="text-muted-foreground">
                                    Peran Anda
                                </dt>
                                <dd className="font-medium">
                                    {auth.roleLabel ?? '-'}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4 py-3">
                                <dt className="flex items-center gap-2 text-muted-foreground">
                                    <Clock3
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Zona waktu
                                </dt>
                                <dd className="font-medium">
                                    {gym?.timezone ?? '-'}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4 py-3">
                                <dt className="text-muted-foreground">
                                    Mata uang
                                </dt>
                                <dd className="font-medium">
                                    {gym?.currency ?? '-'}
                                </dd>
                            </div>
                        </dl>
                    </aside>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
