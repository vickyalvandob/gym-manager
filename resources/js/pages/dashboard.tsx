import { Deferred, Head, Link, router, usePage } from '@inertiajs/react';
import {
    Activity,
    CalendarClock,
    ChartNoAxesCombined,
    CircleDollarSign,
    Clock3,
    RefreshCw,
    UserPlus,
    UserRoundCheck,
    Users,
    UsersRound,
} from 'lucide-react';
import type { ComponentType } from 'react';
import CheckInController from '@/actions/App/Http/Controllers/CheckInController';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import { PaymentStatusBadge } from '@/components/payments/payment-status-badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { formatCurrency, formatDateTime } from '@/lib/formatters';
import { dashboard } from '@/routes';
import { index as membersIndex } from '@/routes/members';
import { index as reportsIndex } from '@/routes/reports';
import type {
    DashboardMetrics,
    DashboardProps,
    DashboardRecentCheckIn,
    DashboardRecentPayment,
    DashboardSnapshot,
} from '@/types';

export default function Dashboard({ snapshot }: DashboardProps) {
    const { auth } = usePage().props;
    const gym = auth.currentGym;

    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div className="min-w-0">
                        <p className="text-sm font-medium text-primary">
                            {gym?.name}
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                            Dashboard
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Ringkasan operasional yang perlu ditindaklanjuti
                            hari ini.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {auth.permissions.operate_front_desk && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={membersIndex()}>
                                    <UsersRound />
                                    Kelola member
                                </Link>
                            </Button>
                        )}
                        {auth.permissions.view_reports && (
                            <Button size="sm" asChild>
                                <Link href={reportsIndex()}>
                                    <ChartNoAxesCombined />
                                    Buka laporan
                                </Link>
                            </Button>
                        )}
                    </div>
                </header>

                <Deferred
                    data="snapshot"
                    fallback={<DashboardSkeleton />}
                    rescue={({ reloading }) => (
                        <DashboardError reloading={reloading} />
                    )}
                >
                    {({ reloading }) =>
                        snapshot ? (
                            <DashboardContent
                                snapshot={snapshot}
                                reloading={reloading}
                            />
                        ) : null
                    }
                </Deferred>
            </div>
        </>
    );
}

function DashboardContent({
    snapshot,
    reloading,
}: {
    snapshot: DashboardSnapshot;
    reloading: boolean;
}) {
    const { auth } = usePage().props;
    const currency = auth.currentGym?.currency ?? 'IDR';
    const timezone = auth.currentGym?.timezone ?? 'Asia/Jakarta';
    const warningDays = auth.currentGym?.membership_expiry_warning_days ?? 7;
    const metrics = metricItems(
        snapshot.metrics,
        currency,
        warningDays,
        Boolean(auth.permissions.view_reports),
    );

    return (
        <div
            className={reloading ? 'space-y-7 opacity-60' : 'space-y-7'}
            aria-busy={reloading}
        >
            <section aria-labelledby="dashboard-summary-heading">
                <div className="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2
                            id="dashboard-summary-heading"
                            className="text-base font-semibold"
                        >
                            Ringkasan utama
                        </h2>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Status membership mengikuti zona waktu gym.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        disabled={reloading}
                        onClick={() => router.reload({ only: ['snapshot'] })}
                    >
                        <RefreshCw
                            className={reloading ? 'animate-spin' : ''}
                        />
                        Segarkan
                    </Button>
                </div>

                <div className="grid overflow-hidden rounded-lg border sm:grid-cols-2 xl:grid-cols-4">
                    {metrics.map((metric) => (
                        <MetricItem key={metric.label} {...metric} />
                    ))}
                </div>
            </section>

            {auth.permissions.operate_front_desk ? (
                <div className="grid gap-7 border-t pt-7 xl:grid-cols-2">
                    <RecentCheckIns
                        checkIns={snapshot.recent_check_ins}
                        timezone={timezone}
                    />
                    <RecentPayments
                        payments={snapshot.recent_payments}
                        currency={currency}
                        timezone={timezone}
                    />
                </div>
            ) : (
                <section className="border-t pt-7" aria-label="Akses trainer">
                    <div className="flex min-h-48 flex-col items-center justify-center border-y px-6 py-10 text-center">
                        <Activity className="size-8 text-muted-foreground" />
                        <p className="mt-3 text-sm font-medium">
                            Ringkasan trainer aktif
                        </p>
                        <p className="mt-1 max-w-md text-xs leading-5 text-muted-foreground">
                            Detail member dan assignment trainer akan tersedia
                            pada fase workspace trainer.
                        </p>
                    </div>
                </section>
            )}

            <p className="flex items-center justify-end gap-1.5 text-xs text-muted-foreground">
                <Clock3 className="size-3.5" aria-hidden="true" />
                Diperbarui {formatDateTime(snapshot.generated_at, timezone)}
            </p>
        </div>
    );
}

function metricItems(
    metrics: DashboardMetrics,
    currency: string,
    warningDays: number,
    canViewRevenue: boolean,
) {
    const items: Array<{
        label: string;
        value: string;
        detail: string;
        icon: ComponentType<{ className?: string; 'aria-hidden'?: boolean }>;
    }> = [
        {
            label: 'Member aktif',
            value: String(metrics.active_members),
            detail: 'Memiliki membership aktif hari ini',
            icon: Users,
        },
        {
            label: 'Membership berakhir',
            value: String(metrics.expired_members),
            detail: 'Belum memiliki periode aktif baru',
            icon: CalendarClock,
        },
        {
            label: 'Segera berakhir',
            value: String(metrics.expiring_soon),
            detail: `Dalam ${warningDays} hari ke depan`,
            icon: CalendarClock,
        },
        {
            label: 'Member baru',
            value: String(metrics.new_members_this_month),
            detail: 'Terdaftar bulan ini',
            icon: UserPlus,
        },
        {
            label: 'Check-in hari ini',
            value: String(metrics.check_ins_today),
            detail: 'Kunjungan yang berhasil dicatat',
            icon: UserRoundCheck,
        },
    ];

    if (canViewRevenue) {
        items.push(
            {
                label: 'Pendapatan hari ini',
                value: formatCurrency(
                    metrics.revenue_today ?? '0.00',
                    currency,
                ),
                detail: 'Hanya invoice berstatus lunas',
                icon: CircleDollarSign,
            },
            {
                label: 'Pendapatan bulan ini',
                value: formatCurrency(
                    metrics.revenue_this_month ?? '0.00',
                    currency,
                ),
                detail: 'Akumulasi sejak awal bulan',
                icon: ChartNoAxesCombined,
            },
        );
    }

    return items;
}

function MetricItem({
    label,
    value,
    detail,
    icon: Icon,
}: {
    label: string;
    value: string;
    detail: string;
    icon: ComponentType<{ className?: string; 'aria-hidden'?: boolean }>;
}) {
    return (
        <article className="border-b p-4 last:border-b-0 sm:border-r xl:border-b-0 xl:[&:nth-child(4n)]:border-r-0">
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                    <p className="text-xs font-medium text-muted-foreground">
                        {label}
                    </p>
                    <p className="mt-2 truncate text-xl font-semibold tabular-nums">
                        {value}
                    </p>
                </div>
                <div className="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                    <Icon className="size-4" aria-hidden />
                </div>
            </div>
            <p className="mt-2 text-xs leading-5 text-muted-foreground">
                {detail}
            </p>
        </article>
    );
}

function RecentCheckIns({
    checkIns,
    timezone,
}: {
    checkIns: DashboardRecentCheckIn[];
    timezone: string;
}) {
    return (
        <section aria-labelledby="recent-check-ins-heading">
            <div className="flex items-end justify-between gap-4">
                <div>
                    <h2
                        id="recent-check-ins-heading"
                        className="text-base font-semibold"
                    >
                        Check-in terbaru
                    </h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Enam kunjungan terakhir.
                    </p>
                </div>
                <Button variant="ghost" size="sm" asChild>
                    <Link href={CheckInController.index()}>Lihat semua</Link>
                </Button>
            </div>

            {checkIns.length === 0 ? (
                <DashboardEmptyState
                    icon={UserRoundCheck}
                    title="Belum ada check-in"
                    description="Kunjungan pertama akan langsung muncul di sini."
                />
            ) : (
                <div className="mt-4 divide-y border-y">
                    {checkIns.map((checkIn) => (
                        <article
                            key={checkIn.id}
                            className="flex items-center justify-between gap-4 py-3"
                        >
                            <div className="min-w-0">
                                <Link
                                    href={MemberController.show(
                                        checkIn.member.id,
                                    )}
                                    className="block truncate text-sm font-medium hover:text-primary"
                                >
                                    {checkIn.member.name}
                                </Link>
                                <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                    {checkIn.member.member_number} ·{' '}
                                    {checkIn.membership.plan_name}
                                </p>
                            </div>
                            <time className="shrink-0 text-xs text-muted-foreground">
                                {formatDateTime(
                                    checkIn.checked_in_at,
                                    timezone,
                                )}
                            </time>
                        </article>
                    ))}
                </div>
            )}
        </section>
    );
}

function RecentPayments({
    payments,
    currency,
    timezone,
}: {
    payments: DashboardRecentPayment[];
    currency: string;
    timezone: string;
}) {
    return (
        <section aria-labelledby="recent-payments-heading">
            <div className="flex items-end justify-between gap-4">
                <div>
                    <h2
                        id="recent-payments-heading"
                        className="text-base font-semibold"
                    >
                        Pembayaran terbaru
                    </h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Invoice yang terakhir dibuat atau diterima.
                    </p>
                </div>
                <Button variant="ghost" size="sm" asChild>
                    <Link href={PaymentController.index()}>Lihat semua</Link>
                </Button>
            </div>

            {payments.length === 0 ? (
                <DashboardEmptyState
                    icon={CircleDollarSign}
                    title="Belum ada pembayaran"
                    description="Invoice membership pertama akan muncul di sini."
                />
            ) : (
                <div className="mt-4 divide-y border-y">
                    {payments.map((payment) => (
                        <article
                            key={payment.id}
                            className="flex items-center justify-between gap-4 py-3"
                        >
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <Link
                                        href={MemberController.show(
                                            payment.member.id,
                                        )}
                                        className="truncate text-sm font-medium hover:text-primary"
                                    >
                                        {payment.member.name}
                                    </Link>
                                    <PaymentStatusBadge
                                        status={payment.status}
                                        label={payment.status_label}
                                    />
                                </div>
                                <p className="mt-0.5 truncate font-mono text-xs text-muted-foreground">
                                    {payment.invoice_number} ·{' '}
                                    {formatDateTime(
                                        payment.paid_at ?? payment.created_at,
                                        timezone,
                                    )}
                                </p>
                            </div>
                            <p className="shrink-0 text-sm font-semibold tabular-nums">
                                {formatCurrency(payment.amount, currency)}
                            </p>
                        </article>
                    ))}
                </div>
            )}
        </section>
    );
}

function DashboardEmptyState({
    icon: Icon,
    title,
    description,
}: {
    icon: ComponentType<{ className?: string }>;
    title: string;
    description: string;
}) {
    return (
        <div className="mt-4 flex min-h-48 flex-col items-center justify-center border-y px-6 py-8 text-center">
            <Icon className="size-8 text-muted-foreground" />
            <p className="mt-3 text-sm font-medium">{title}</p>
            <p className="mt-1 text-xs text-muted-foreground">{description}</p>
        </div>
    );
}

function DashboardSkeleton() {
    return (
        <div className="space-y-7" aria-label="Memuat dashboard">
            <div className="grid overflow-hidden rounded-lg border sm:grid-cols-2 xl:grid-cols-4">
                {Array.from({ length: 7 }, (_, index) => (
                    <div key={index} className="border-b p-4 sm:border-r">
                        <Skeleton className="h-3 w-24" />
                        <Skeleton className="mt-3 h-7 w-20" />
                        <Skeleton className="mt-3 h-3 w-40 max-w-full" />
                    </div>
                ))}
            </div>
            <div className="grid gap-7 border-t pt-7 xl:grid-cols-2">
                {[0, 1].map((section) => (
                    <div key={section}>
                        <Skeleton className="h-5 w-40" />
                        <div className="mt-4 space-y-4 border-y py-4">
                            {[0, 1, 2].map((row) => (
                                <Skeleton key={row} className="h-9 w-full" />
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function DashboardError({ reloading }: { reloading: boolean }) {
    return (
        <div className="flex min-h-64 flex-col items-center justify-center border-y px-6 py-12 text-center">
            <Activity className="size-9 text-muted-foreground" />
            <p className="mt-4 text-sm font-medium">
                Ringkasan belum dapat dimuat
            </p>
            <p className="mt-1 text-xs text-muted-foreground">
                Coba muat ulang data dashboard.
            </p>
            <Button
                type="button"
                variant="outline"
                size="sm"
                className="mt-4"
                disabled={reloading}
                onClick={() => router.reload({ only: ['snapshot'] })}
            >
                <RefreshCw className={reloading ? 'animate-spin' : ''} />
                Coba lagi
            </Button>
        </div>
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
