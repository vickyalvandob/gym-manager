import { Deferred, Head, Link, router, usePage } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    ArrowRight,
    CalendarClock,
    ChartNoAxesCombined,
    Clock3,
    Dumbbell,
    LogIn,
    Plus,
    ReceiptText,
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
import PtSessionController from '@/actions/App/Http/Controllers/PtSessionController';
import { PaymentStatusBadge } from '@/components/payments/payment-status-badge';
import { PtSessionStatusBadge } from '@/components/personal-training/pt-session-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { formatCurrency, formatDate, formatDateTime } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import {
    create as createMember,
    index as membersIndex,
} from '@/routes/members';
import { index as reportsIndex } from '@/routes/reports';
import {
    index as trainerMembersIndex,
    show as trainerMemberShow,
} from '@/routes/trainer-members';
import type {
    DashboardMetrics,
    DashboardProps,
    DashboardRecentCheckIn,
    DashboardRecentPayment,
    DashboardRevenuePoint,
    DashboardSnapshot,
    GymRole,
    TrainerDashboardWorkspace,
} from '@/types';

type DashboardIcon = ComponentType<{
    className?: string;
    'aria-hidden'?: boolean;
}>;

export default function Dashboard({ snapshot }: DashboardProps) {
    const { auth } = usePage().props;
    const headerCopy = dashboardHeaderCopy(auth.role);

    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto flex w-full max-w-[90rem] flex-1 flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8 lg:py-9">
                <header className="flex flex-col justify-between gap-5 border-b pb-6 sm:flex-row sm:items-end lg:pb-7">
                    <div className="min-w-0">
                        <p className="truncate text-[11px] font-semibold tracking-[0.16em] text-primary uppercase">
                            {auth.currentGym?.name} · {headerCopy.roleLabel}
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold tracking-[-0.03em] sm:text-3xl">
                            {headerCopy.title}
                        </h1>
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                            {headerCopy.description}
                        </p>
                    </div>

                    <DashboardHeaderActions
                        role={auth.role}
                        canOperateFrontDesk={Boolean(
                            auth.permissions.operate_front_desk,
                        )}
                        canViewReports={Boolean(auth.permissions.view_reports)}
                    />
                </header>

                <Deferred
                    data="snapshot"
                    fallback={
                        <DashboardSkeleton
                            isTrainer={auth.role === 'trainer'}
                        />
                    }
                    rescue={({ reloading }) => (
                        <DashboardError reloading={reloading} />
                    )}
                >
                    {({ reloading }) =>
                        snapshot ? (
                            <DashboardContent
                                snapshot={snapshot}
                                role={auth.role}
                                currency={auth.currentGym?.currency ?? 'IDR'}
                                timezone={
                                    auth.currentGym?.timezone ?? 'Asia/Jakarta'
                                }
                                warningDays={
                                    auth.currentGym
                                        ?.membership_expiry_warning_days ?? 7
                                }
                                reloading={reloading}
                            />
                        ) : null
                    }
                </Deferred>
            </div>
        </>
    );
}

function DashboardHeaderActions({
    role,
    canOperateFrontDesk,
    canViewReports,
}: {
    role: GymRole | null;
    canOperateFrontDesk: boolean;
    canViewReports: boolean;
}) {
    if (role === 'trainer') {
        return (
            <div className="flex flex-wrap gap-2">
                <Button
                    className="shadow-none"
                    variant="outline"
                    size="sm"
                    asChild
                >
                    <Link href={trainerMembersIndex()} prefetch>
                        <UsersRound />
                        Member saya
                    </Link>
                </Button>
                <Button className="shadow-none" size="sm" asChild>
                    <Link
                        href={PtSessionController.index({
                            query: { scope: 'today' },
                        })}
                        prefetch
                    >
                        <CalendarClock />
                        Jadwal hari ini
                    </Link>
                </Button>
            </div>
        );
    }

    if (role === 'owner') {
        return (
            <div className="flex flex-wrap gap-2">
                {canOperateFrontDesk && (
                    <Button
                        className="shadow-none"
                        variant="outline"
                        size="sm"
                        asChild
                    >
                        <Link href={membersIndex()} prefetch>
                            <UsersRound />
                            Kelola member
                        </Link>
                    </Button>
                )}
                {canViewReports && (
                    <Button className="shadow-none" size="sm" asChild>
                        <Link href={reportsIndex()} prefetch>
                            <ChartNoAxesCombined />
                            Buka laporan
                        </Link>
                    </Button>
                )}
            </div>
        );
    }

    if (!canOperateFrontDesk) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-2">
            <Button className="shadow-none" variant="outline" size="sm" asChild>
                <Link href={createMember()} prefetch>
                    <Plus />
                    Tambah member
                </Link>
            </Button>
            <Button className="shadow-none" size="sm" asChild>
                <Link href={CheckInController.index()} prefetch>
                    <LogIn />
                    Proses check-in
                </Link>
            </Button>
        </div>
    );
}

function DashboardContent({
    snapshot,
    role,
    currency,
    timezone,
    warningDays,
    reloading,
}: {
    snapshot: DashboardSnapshot;
    role: GymRole | null;
    currency: string;
    timezone: string;
    warningDays: number;
    reloading: boolean;
}) {
    if (role === 'trainer' && snapshot.trainer_workspace) {
        return (
            <TrainerWorkspace
                workspace={snapshot.trainer_workspace}
                generatedAt={snapshot.generated_at}
                timezone={timezone}
                reloading={reloading}
            />
        );
    }

    return (
        <ManagementWorkspace
            metrics={snapshot.metrics}
            revenueTrend={snapshot.revenue_trend}
            checkIns={snapshot.recent_check_ins}
            payments={snapshot.recent_payments}
            generatedAt={snapshot.generated_at}
            currency={currency}
            timezone={timezone}
            warningDays={warningDays}
            isOwner={role === 'owner'}
            reloading={reloading}
        />
    );
}

function ManagementWorkspace({
    metrics,
    revenueTrend,
    checkIns,
    payments,
    generatedAt,
    currency,
    timezone,
    warningDays,
    isOwner,
    reloading,
}: {
    metrics: DashboardMetrics;
    revenueTrend: DashboardRevenuePoint[] | null;
    checkIns: DashboardRecentCheckIn[];
    payments: DashboardRecentPayment[];
    generatedAt: string;
    currency: string;
    timezone: string;
    warningDays: number;
    isOwner: boolean;
    reloading: boolean;
}) {
    return (
        <div
            className={cn(
                'space-y-8 transition-opacity',
                reloading && 'opacity-60',
            )}
            aria-busy={reloading}
        >
            <section aria-labelledby="dashboard-focus-heading">
                <div className="mb-5 flex items-end justify-between gap-4">
                    <div>
                        <h2
                            id="dashboard-focus-heading"
                            className="text-sm font-semibold tracking-[0.08em] uppercase"
                        >
                            {isOwner ? 'Ringkasan bisnis' : 'Fokus hari ini'}
                        </h2>
                        <p className="mt-1 text-xs leading-5 text-muted-foreground">
                            {isOwner
                                ? 'Pendapatan, kesehatan membership, dan aktivitas terbaru.'
                                : 'Angka operasional yang paling perlu dipantau Front Office.'}
                        </p>
                    </div>
                    <RefreshDashboardButton reloading={reloading} />
                </div>

                {isOwner ? (
                    <OwnerOverview
                        metrics={metrics}
                        revenueTrend={revenueTrend ?? []}
                        currency={currency}
                        warningDays={warningDays}
                    />
                ) : (
                    <FrontOfficeOverview
                        metrics={metrics}
                        currency={currency}
                        warningDays={warningDays}
                    />
                )}
            </section>

            <section
                className="grid items-start gap-4 border-t pt-8 xl:grid-cols-2"
                aria-label="Aktivitas terbaru"
            >
                <RecentCheckIns checkIns={checkIns} timezone={timezone} />
                <RecentPayments
                    payments={payments}
                    currency={currency}
                    timezone={timezone}
                />
            </section>

            <DashboardFreshness generatedAt={generatedAt} timezone={timezone} />
        </div>
    );
}

function OwnerOverview({
    metrics,
    revenueTrend,
    currency,
    warningDays,
}: {
    metrics: DashboardMetrics;
    revenueTrend: DashboardRevenuePoint[];
    currency: string;
    warningDays: number;
}) {
    return (
        <div className="space-y-4">
            <div className="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(19rem,0.6fr)]">
                <article className="rounded-lg border bg-background p-5 sm:p-6">
                    <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0">
                            <p className="text-xs font-medium text-muted-foreground">
                                Pendapatan bulan ini
                            </p>
                            <p className="mt-2 text-2xl font-semibold tracking-tight break-words tabular-nums sm:text-4xl">
                                {formatCurrency(
                                    metrics.revenue_this_month ?? '0.00',
                                    currency,
                                )}
                            </p>
                        </div>
                        <span className="flex size-9 shrink-0 items-center justify-center rounded-md border text-primary">
                            <ChartNoAxesCombined
                                className="size-4.5"
                                aria-hidden
                            />
                        </span>
                    </div>

                    <div className="mt-6">
                        <div className="flex items-center justify-between gap-3">
                            <p className="text-xs font-medium text-muted-foreground">
                                Tren 7 hari terakhir
                            </p>
                            <span className="text-[11px] text-muted-foreground">
                                Pembayaran lunas
                            </span>
                        </div>
                        <RevenueTrendChart
                            points={revenueTrend}
                            currency={currency}
                        />
                    </div>

                    <div className="mt-5 flex flex-col justify-between gap-4 border-t pt-4 sm:flex-row sm:items-end">
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Diterima hari ini
                            </p>
                            <p className="mt-1 text-lg font-semibold tabular-nums">
                                {formatCurrency(
                                    metrics.revenue_today ?? '0.00',
                                    currency,
                                )}
                            </p>
                        </div>
                        <Link
                            href={reportsIndex()}
                            prefetch
                            className="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
                        >
                            Analisis laporan
                            <ArrowRight className="size-4" aria-hidden />
                        </Link>
                    </div>
                </article>

                <section className="rounded-lg border bg-background p-5">
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <h3 className="text-sm font-semibold">
                                Perlu perhatian
                            </h3>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Prioritas retensi membership.
                            </p>
                        </div>
                        <AlertTriangle
                            className="size-4.5 text-muted-foreground"
                            aria-hidden
                        />
                    </div>

                    <div className="mt-4 divide-y">
                        <PriorityRow
                            label="Segera berakhir"
                            detail={`Dalam ${warningDays} hari`}
                            value={metrics.expiring_soon}
                            tone="attention"
                        />
                        <PriorityRow
                            label="Membership berakhir"
                            detail="Belum memiliki periode aktif"
                            value={metrics.expired_members}
                            tone="critical"
                        />
                    </div>

                    <Button
                        variant="outline"
                        size="sm"
                        className="mt-4 w-full shadow-none"
                        asChild
                    >
                        <Link href={membersIndex()} prefetch>
                            Tinjau member
                            <ArrowRight />
                        </Link>
                    </Button>
                </section>
            </div>

            <div className="grid gap-3 sm:grid-cols-3">
                <StatCard
                    label="Member aktif"
                    value={metrics.active_members}
                    detail="Membership aktif hari ini"
                    icon={Users}
                />
                <StatCard
                    label="Check-in hari ini"
                    value={metrics.check_ins_today}
                    detail="Kunjungan berhasil dicatat"
                    icon={UserRoundCheck}
                />
                <StatCard
                    label="Member baru"
                    value={metrics.new_members_this_month}
                    detail="Terdaftar bulan ini"
                    icon={UserPlus}
                />
            </div>
        </div>
    );
}

function RevenueTrendChart({
    points,
    currency,
}: {
    points: DashboardRevenuePoint[];
    currency: string;
}) {
    const maximumRevenue = Math.max(
        ...points.map((point) => Number(point.amount)),
        0,
    );

    return (
        <figure className="mt-3">
            <div
                className="flex h-36 items-end gap-2 rounded-md border bg-muted/20 px-3 pt-4 pb-2 sm:gap-3"
                role="img"
                aria-label="Grafik pendapatan tujuh hari terakhir"
                aria-describedby="owner-revenue-chart-description"
            >
                {points.map((point) => {
                    const amount = Number(point.amount);
                    const height =
                        maximumRevenue === 0
                            ? 4
                            : Math.max(8, (amount / maximumRevenue) * 100);

                    return (
                        <div
                            key={point.date}
                            className="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2"
                            title={`${formatRevenueDate(point.date)}: ${formatCurrency(point.amount, currency)}`}
                        >
                            <span
                                className={cn(
                                    'w-full max-w-10 rounded-t-sm bg-primary/80 transition-[height]',
                                    amount === 0 && 'bg-border',
                                )}
                                style={{ height: `${height}%` }}
                                aria-hidden
                            />
                            <span className="max-w-full truncate text-[10px] text-muted-foreground sm:text-[11px]">
                                {formatRevenueDate(point.date)}
                            </span>
                        </div>
                    );
                })}
            </div>
            <figcaption
                id="owner-revenue-chart-description"
                className="sr-only"
            >
                {points
                    .map(
                        (point) =>
                            `${formatRevenueDate(point.date)}: ${formatCurrency(point.amount, currency)}`,
                    )
                    .join('; ')}
            </figcaption>
        </figure>
    );
}

function formatRevenueDate(date: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'short',
        day: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${date}T00:00:00Z`));
}

function FrontOfficeOverview({
    metrics,
    currency,
    warningDays,
}: {
    metrics: DashboardMetrics;
    currency: string;
    warningDays: number;
}) {
    return (
        <div className="grid gap-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(19rem,0.85fr)]">
            <article className="rounded-lg border bg-background p-5 sm:p-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-xs font-medium text-muted-foreground">
                            Check-in hari ini
                        </p>
                        <p className="mt-2 text-4xl font-semibold tracking-tight tabular-nums">
                            {metrics.check_ins_today}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Kunjungan berhasil tercatat
                        </p>
                    </div>
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-md border text-primary">
                        <UserRoundCheck className="size-5" aria-hidden />
                    </span>
                </div>

                <div className="mt-5 grid grid-cols-2 gap-3 border-t pt-4">
                    <div>
                        <p className="text-xs text-muted-foreground">
                            Member aktif
                        </p>
                        <p className="mt-1 text-lg font-semibold tabular-nums">
                            {metrics.active_members}
                        </p>
                    </div>
                    <div>
                        <p className="text-xs text-muted-foreground">
                            Member baru bulan ini
                        </p>
                        <p className="mt-1 text-lg font-semibold tabular-nums">
                            {metrics.new_members_this_month}
                        </p>
                    </div>
                </div>

                <Button
                    className="mt-5 w-full shadow-none sm:w-auto"
                    size="sm"
                    asChild
                >
                    <Link href={CheckInController.index()} prefetch>
                        <LogIn />
                        Proses check-in
                    </Link>
                </Button>
            </article>

            <section className="rounded-lg border bg-background p-5">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h3 className="text-sm font-semibold">
                            Antrean tindak lanjut
                        </h3>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Selesaikan yang paling mendesak lebih dulu.
                        </p>
                    </div>
                    <AlertTriangle
                        className="size-4.5 text-muted-foreground"
                        aria-hidden
                    />
                </div>

                <div className="mt-4 divide-y">
                    <PriorityRow
                        label="Segera berakhir"
                        detail={`${warningDays} hari ke depan`}
                        value={metrics.expiring_soon}
                        tone="attention"
                    />
                    <PriorityRow
                        label="Membership berakhir"
                        detail="Perlu dihubungi kembali"
                        value={metrics.expired_members}
                        tone="critical"
                    />
                    <PriorityRow
                        label="Tagihan belum lunas"
                        detail={formatCurrency(
                            metrics.pending_payments_amount ?? '0.00',
                            currency,
                        )}
                        value={metrics.pending_payments_count ?? 0}
                        tone="attention"
                    />
                </div>

                <Button
                    variant="outline"
                    size="sm"
                    className="mt-4 w-full shadow-none"
                    asChild
                >
                    <Link href={PaymentController.index()} prefetch>
                        Buka pembayaran
                        <ArrowRight />
                    </Link>
                </Button>
            </section>
        </div>
    );
}

function StatCard({
    label,
    value,
    detail,
    icon: Icon,
    tone = 'default',
}: {
    label: string;
    value: number | string;
    detail: string;
    icon: DashboardIcon;
    tone?: 'default' | 'attention' | 'critical';
}) {
    return (
        <article className="rounded-lg border bg-background p-4">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-xs font-medium text-muted-foreground">
                        {label}
                    </p>
                    <p className="mt-2 text-2xl font-semibold tracking-tight tabular-nums">
                        {value}
                    </p>
                </div>
                <span
                    className={cn(
                        'flex size-8 shrink-0 items-center justify-center rounded-md border text-muted-foreground',
                        tone === 'attention' &&
                            'border-amber-500/30 bg-amber-500/5 text-amber-700 dark:text-amber-400',
                        tone === 'critical' &&
                            'border-destructive/30 bg-destructive/5 text-destructive',
                    )}
                >
                    <Icon className="size-4" aria-hidden />
                </span>
            </div>
            <p className="mt-2 text-xs leading-5 text-muted-foreground">
                {detail}
            </p>
        </article>
    );
}

function PriorityRow({
    label,
    detail,
    value,
    tone,
}: {
    label: string;
    detail: string;
    value: number;
    tone: 'attention' | 'critical';
}) {
    return (
        <div className="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
            <div className="min-w-0">
                <p className="text-sm font-medium">{label}</p>
                <p className="mt-0.5 text-xs text-muted-foreground">{detail}</p>
            </div>
            <span
                className={cn(
                    'rounded-md px-2 py-1 text-sm font-semibold tabular-nums',
                    tone === 'attention'
                        ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400'
                        : 'bg-destructive/10 text-destructive',
                )}
            >
                {value}
            </span>
        </div>
    );
}

function TrainerWorkspace({
    workspace,
    generatedAt,
    timezone,
    reloading,
}: {
    workspace: TrainerDashboardWorkspace;
    generatedAt: string;
    timezone: string;
    reloading: boolean;
}) {
    if (!workspace.trainer) {
        return (
            <div
                className={cn(
                    'space-y-5 transition-opacity',
                    reloading && 'opacity-60',
                )}
                aria-busy={reloading}
            >
                <div className="flex justify-end">
                    <RefreshDashboardButton reloading={reloading} />
                </div>
                <section className="flex min-h-72 flex-col items-center justify-center rounded-lg border border-dashed bg-background px-6 py-12 text-center">
                    <Dumbbell className="size-9 text-muted-foreground" />
                    <h2 className="mt-4 text-base font-semibold">
                        Profil trainer belum terhubung
                    </h2>
                    <p className="mt-1 max-w-md text-sm leading-6 text-muted-foreground">
                        Minta Owner menghubungkan akun ini ke profil trainer
                        agar jadwal dan member Anda dapat ditampilkan.
                    </p>
                </section>
            </div>
        );
    }

    return (
        <div
            className={cn(
                'space-y-8 transition-opacity',
                reloading && 'opacity-60',
            )}
            aria-busy={reloading}
        >
            <section className="flex flex-col justify-between gap-4 rounded-lg border bg-background p-5 sm:flex-row sm:items-center">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="truncate text-base font-semibold">
                            {workspace.trainer.name}
                        </h2>
                        <Badge variant="outline">Profil aktif</Badge>
                        <Badge variant="secondary">
                            {workspace.assigned_members_count} member ditangani
                        </Badge>
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {workspace.trainer.specialization ??
                            'Spesialisasi belum dicatat'}
                    </p>
                </div>
                <RefreshDashboardButton reloading={reloading} />
            </section>

            <section aria-labelledby="trainer-summary-heading">
                <div className="mb-5">
                    <h2
                        id="trainer-summary-heading"
                        className="text-sm font-semibold tracking-[0.08em] uppercase"
                    >
                        Ringkasan agenda
                    </h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Jadwal dan kapasitas pendampingan Anda.
                    </p>
                </div>
                <div className="grid gap-3 sm:grid-cols-3">
                    <StatCard
                        label="Sesi hari ini"
                        value={workspace.today_sessions_count}
                        detail="Semua status jadwal"
                        icon={CalendarClock}
                    />
                    <StatCard
                        label="Jadwal mendatang"
                        value={workspace.upcoming_sessions_count}
                        detail="Sesi terjadwal berikutnya"
                        icon={Clock3}
                    />
                    <StatCard
                        label="Member sesi aktif"
                        value={workspace.active_pt_clients_count}
                        detail="Memiliki paket PT berjalan"
                        icon={Dumbbell}
                    />
                </div>
            </section>

            <section className="border-t pt-8">
                <TrainerTodaySessions
                    sessions={workspace.today_sessions}
                    timezone={timezone}
                />
            </section>

            <section
                className="border-t pt-8"
                aria-labelledby="trainer-members-heading"
            >
                <div className="flex items-end justify-between gap-4">
                    <div>
                        <h2
                            id="trainer-members-heading"
                            className="text-base font-semibold"
                        >
                            Member sesi saya
                        </h2>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Paket PT aktif dan sisa sesi yang perlu dijadwalkan.
                        </p>
                    </div>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={trainerMembersIndex()} prefetch>
                            Lihat semua
                            <ArrowRight />
                        </Link>
                    </Button>
                </div>

                {workspace.session_members.length === 0 ? (
                    <DashboardEmptyState
                        icon={Dumbbell}
                        title="Belum ada paket sesi aktif"
                        description="Member dengan paket PT aktif akan tampil di bagian ini."
                    />
                ) : (
                    <div className="mt-4 grid gap-3 md:grid-cols-2">
                        {workspace.session_members.map((item) => (
                            <Link
                                key={item.id}
                                href={trainerMemberShow(item.member.id)}
                                prefetch
                                className="group rounded-lg border bg-background p-4 transition-colors hover:border-primary/30 hover:bg-muted/30 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium group-hover:text-primary">
                                            {item.member.name}
                                        </p>
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            {item.member.member_number} ·{' '}
                                            {item.pt_package_name}
                                        </p>
                                    </div>
                                    <Badge variant="outline">
                                        {item.available_sessions} tersedia
                                    </Badge>
                                </div>
                                <div className="mt-3 flex flex-wrap items-center justify-between gap-2 border-t pt-3 text-xs text-muted-foreground">
                                    <span className="tabular-nums">
                                        {item.remaining_sessions} sesi tersisa
                                    </span>
                                    <span>
                                        Berlaku sampai{' '}
                                        {formatDate(item.expires_at, timezone)}
                                    </span>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </section>

            <DashboardFreshness generatedAt={generatedAt} timezone={timezone} />
        </div>
    );
}

function TrainerTodaySessions({
    sessions,
    timezone,
}: {
    sessions: TrainerDashboardWorkspace['today_sessions'];
    timezone: string;
}) {
    return (
        <section className="rounded-lg border bg-background p-5">
            <div className="flex items-end justify-between gap-4">
                <div>
                    <h2 className="text-base font-semibold">Sesi hari ini</h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Diurutkan dari jadwal paling awal.
                    </p>
                </div>
                <Button variant="ghost" size="sm" asChild>
                    <Link
                        href={PtSessionController.index({
                            query: { scope: 'today' },
                        })}
                        prefetch
                    >
                        Lihat jadwal
                    </Link>
                </Button>
            </div>

            {sessions.length === 0 ? (
                <DashboardEmptyState
                    icon={CalendarClock}
                    title="Tidak ada sesi hari ini"
                    description="Agenda Anda kosong. Jadwal baru akan muncul di sini."
                    compact
                />
            ) : (
                <div className="mt-4 divide-y">
                    {sessions.map((session) => (
                        <Link
                            key={session.id}
                            href={PtSessionController.show(session.id)}
                            prefetch
                            className="group flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
                        >
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium group-hover:text-primary">
                                    {session.member.name}
                                </p>
                                <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                    {formatDateTime(
                                        session.scheduled_at,
                                        timezone,
                                    )}{' '}
                                    · {session.duration_minutes} menit ·{' '}
                                    {session.pt_package_name}
                                </p>
                            </div>
                            <PtSessionStatusBadge
                                status={session.status}
                                label={session.status_label}
                            />
                        </Link>
                    ))}
                </div>
            )}
        </section>
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
        <section className="rounded-lg border bg-background p-5">
            <div className="flex items-end justify-between gap-4">
                <div>
                    <h2 className="text-base font-semibold">
                        Check-in terbaru
                    </h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Enam kunjungan terakhir.
                    </p>
                </div>
                <Button variant="ghost" size="sm" asChild>
                    <Link href={CheckInController.index()} prefetch>
                        Lihat semua
                    </Link>
                </Button>
            </div>

            {checkIns.length === 0 ? (
                <DashboardEmptyState
                    icon={UserRoundCheck}
                    title="Belum ada check-in"
                    description="Kunjungan pertama akan langsung muncul di sini."
                    compact
                />
            ) : (
                <div className="mt-4 divide-y">
                    {checkIns.map((checkIn) => (
                        <article
                            key={checkIn.id}
                            className="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
                        >
                            <div className="min-w-0">
                                <Link
                                    href={MemberController.show(
                                        checkIn.member.id,
                                    )}
                                    prefetch
                                    className="block truncate text-sm font-medium hover:text-primary"
                                >
                                    {checkIn.member.name}
                                </Link>
                                <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                    {checkIn.member.member_number} ·{' '}
                                    {checkIn.membership.plan_name}
                                </p>
                            </div>
                            <time className="shrink-0 text-right text-xs text-muted-foreground">
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
        <section className="rounded-lg border bg-background p-5">
            <div className="flex items-end justify-between gap-4">
                <div>
                    <h2 className="text-base font-semibold">
                        Transaksi terbaru
                    </h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Invoice yang terakhir dibuat atau diterima.
                    </p>
                </div>
                <Button variant="ghost" size="sm" asChild>
                    <Link
                        href={PaymentController.index({
                            query: { type: 'membership' },
                        })}
                        prefetch
                    >
                        Lihat semua
                    </Link>
                </Button>
            </div>

            {payments.length === 0 ? (
                <DashboardEmptyState
                    icon={ReceiptText}
                    title="Belum ada transaksi"
                    description="Invoice membership pertama akan muncul di sini."
                    compact
                />
            ) : (
                <div className="mt-4 divide-y">
                    {payments.map((payment) => (
                        <article
                            key={payment.id}
                            className="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
                        >
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <Link
                                        href={MemberController.show(
                                            payment.member.id,
                                        )}
                                        prefetch
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
    compact = false,
}: {
    icon: ComponentType<{ className?: string }>;
    title: string;
    description: string;
    compact?: boolean;
}) {
    return (
        <div
            className={cn(
                'mt-4 flex flex-col items-center justify-center rounded-md border border-dashed bg-muted/15 px-6 py-8 text-center',
                compact ? 'min-h-40' : 'min-h-48',
            )}
        >
            <Icon className="size-7 text-muted-foreground" />
            <p className="mt-3 text-sm font-medium">{title}</p>
            <p className="mt-1 max-w-sm text-xs leading-5 text-muted-foreground">
                {description}
            </p>
        </div>
    );
}

function RefreshDashboardButton({ reloading }: { reloading: boolean }) {
    return (
        <Button
            type="button"
            variant="ghost"
            size="sm"
            disabled={reloading}
            onClick={() =>
                router.reload({
                    only: ['snapshot'],
                })
            }
        >
            <RefreshCw
                className={cn(
                    'motion-reduce:animate-none',
                    reloading && 'animate-spin',
                )}
            />
            {reloading ? 'Memuat...' : 'Segarkan'}
        </Button>
    );
}

function DashboardFreshness({
    generatedAt,
    timezone,
}: {
    generatedAt: string;
    timezone: string;
}) {
    return (
        <p
            className="flex items-center justify-end gap-1.5 text-xs text-muted-foreground"
            aria-live="polite"
        >
            <Clock3 className="size-3.5" aria-hidden />
            Diperbarui {formatDateTime(generatedAt, timezone)}
        </p>
    );
}

function DashboardSkeleton({ isTrainer }: { isTrainer: boolean }) {
    return (
        <div
            className="space-y-8"
            aria-label={
                isTrainer
                    ? 'Memuat workspace trainer'
                    : 'Memuat ringkasan dashboard'
            }
        >
            <span className="sr-only">Memuat data dashboard</span>
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {Array.from({ length: 4 }, (_, index) => (
                    <div
                        key={index}
                        className="rounded-lg border bg-background p-4"
                    >
                        <Skeleton className="h-3 w-24" />
                        <Skeleton className="mt-3 h-8 w-20" />
                        <Skeleton className="mt-3 h-3 w-36 max-w-full" />
                    </div>
                ))}
            </div>
            <div className="grid gap-4 border-t pt-8 xl:grid-cols-2">
                {[0, 1].map((section) => (
                    <div
                        key={section}
                        className="rounded-lg border bg-background p-5"
                    >
                        <Skeleton className="h-5 w-40" />
                        <Skeleton className="mt-2 h-3 w-52 max-w-full" />
                        <div className="mt-5 space-y-4">
                            {[0, 1, 2].map((row) => (
                                <Skeleton key={row} className="h-10 w-full" />
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
        <div className="flex min-h-64 flex-col items-center justify-center rounded-lg border border-dashed bg-background px-6 py-12 text-center">
            <Activity className="size-9 text-muted-foreground" />
            <p className="mt-4 text-sm font-medium">
                Ringkasan belum dapat dimuat
            </p>
            <p className="mt-1 text-xs text-muted-foreground">
                Coba muat ulang data dashboard tanpa meninggalkan halaman ini.
            </p>
            <Button
                type="button"
                variant="outline"
                size="sm"
                className="mt-4 shadow-none"
                disabled={reloading}
                onClick={() =>
                    router.reload({
                        only: ['snapshot'],
                    })
                }
            >
                <RefreshCw
                    className={cn(
                        'motion-reduce:animate-none',
                        reloading && 'animate-spin',
                    )}
                />
                {reloading ? 'Memuat...' : 'Coba lagi'}
            </Button>
        </div>
    );
}

function dashboardHeaderCopy(role: GymRole | null): {
    roleLabel: string;
    title: string;
    description: string;
} {
    if (role === 'owner') {
        return {
            roleLabel: 'Owner',
            title: 'Kinerja gym hari ini',
            description:
                'Pantau pendapatan, kesehatan membership, dan aktivitas operasional dalam satu tampilan.',
        };
    }

    if (role === 'trainer') {
        return {
            roleLabel: 'Trainer',
            title: 'Agenda latihan hari ini',
            description:
                'Prioritaskan jadwal sesi, kondisi paket, dan member yang menjadi tanggung jawab Anda.',
        };
    }

    return {
        roleLabel: 'Front Office',
        title: 'Operasional hari ini',
        description:
            'Proses check-in, pantau masa aktif membership, dan tindak lanjuti kebutuhan member.',
    };
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
