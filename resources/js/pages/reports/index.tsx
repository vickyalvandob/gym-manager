import { Deferred, Head, Link, router, usePage } from '@inertiajs/react';
import {
    CalendarClock,
    ChartNoAxesCombined,
    CircleDollarSign,
    Dumbbell,
    RefreshCw,
    UserRoundCheck,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import type { ComponentType, FormEvent } from 'react';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import ReportController from '@/actions/App/Http/Controllers/ReportController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { formatCurrency, formatDate } from '@/lib/formatters';
import { index as reportsIndex } from '@/routes/reports';
import type {
    ReportData,
    ReportIndexProps,
    ReportPeriod,
    ReportRange,
} from '@/types';

export default function ReportsIndex({
    filters,
    range,
    periodOptions,
    report,
}: ReportIndexProps) {
    const { auth, errors } = usePage().props;
    const timezone = auth.currentGym?.timezone ?? 'Asia/Jakarta';
    const [period, setPeriod] = useState<ReportPeriod>(filters.period);
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const isCustom = period === 'custom';

    function submitFilters(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get(
            ReportController.url(),
            {
                period,
                date_from: isCustom ? dateFrom : '',
                date_to: isCustom ? dateTo : '',
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Laporan" />

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">
                        {auth.currentGym?.name}
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                        Laporan
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Revenue, member, membership, dan kunjungan dalam satu
                        rentang yang konsisten.
                    </p>
                </header>

                <form
                    onSubmit={submitFilters}
                    className="grid gap-3 border-y py-4 md:grid-cols-2 xl:grid-cols-[13rem_11rem_11rem_auto_1fr]"
                    aria-label="Filter periode laporan"
                >
                    <div>
                        <label htmlFor="report-period" className="sr-only">
                            Periode laporan
                        </label>
                        <select
                            id="report-period"
                            value={period}
                            onChange={(event) =>
                                setPeriod(event.target.value as ReportPeriod)
                            }
                            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        >
                            {periodOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    {isCustom ? (
                        <>
                            <div>
                                <Input
                                    type="date"
                                    value={dateFrom}
                                    max={dateTo || undefined}
                                    onChange={(event) =>
                                        setDateFrom(event.target.value)
                                    }
                                    aria-label="Tanggal laporan mulai"
                                />
                                <InputError message={errors.date_from} />
                            </div>
                            <div>
                                <Input
                                    type="date"
                                    value={dateTo}
                                    min={dateFrom || undefined}
                                    onChange={(event) =>
                                        setDateTo(event.target.value)
                                    }
                                    aria-label="Tanggal laporan akhir"
                                />
                                <InputError message={errors.date_to} />
                            </div>
                        </>
                    ) : (
                        <p className="flex items-center text-xs text-muted-foreground md:col-span-2">
                            {range.days} hari · {formatRange(range, timezone)}
                        </p>
                    )}

                    <Button type="submit" variant="outline">
                        <ChartNoAxesCombined />
                        Terapkan
                    </Button>

                    <p className="self-center text-right text-xs text-muted-foreground max-xl:hidden">
                        Maksimal 366 hari per laporan
                    </p>
                </form>

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium">{range.label}</p>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {formatRange(range, timezone)} · {range.days} hari
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => router.reload({ only: ['report'] })}
                    >
                        <RefreshCw />
                        Segarkan data
                    </Button>
                </div>

                <Deferred
                    data="report"
                    fallback={<ReportSkeleton />}
                    rescue={({ reloading }) => (
                        <ReportError reloading={reloading} />
                    )}
                >
                    {({ reloading }) =>
                        report ? (
                            <ReportContent
                                report={report}
                                range={range}
                                reloading={reloading}
                            />
                        ) : null
                    }
                </Deferred>
            </div>
        </>
    );
}

function ReportContent({
    report,
    range,
    reloading,
}: {
    report: ReportData;
    range: ReportRange;
    reloading: boolean;
}) {
    const { auth } = usePage().props;
    const currency = auth.currentGym?.currency ?? 'IDR';

    return (
        <div
            className={reloading ? 'space-y-8 opacity-60' : 'space-y-8'}
            aria-busy={reloading}
        >
            <section aria-labelledby="revenue-report-heading">
                <SectionHeading
                    id="revenue-report-heading"
                    icon={CircleDollarSign}
                    title="Revenue"
                    description="Hanya pembayaran lunas berdasarkan waktu penerimaan."
                />
                <div className="mt-4 grid overflow-hidden rounded-lg border sm:grid-cols-3">
                    <SummaryItem
                        label="Total revenue"
                        value={formatCurrency(report.revenue.total, currency)}
                        detail="Sesuai periode terpilih"
                    />
                    <SummaryItem
                        label="Pembayaran lunas"
                        value={String(report.revenue.payment_count)}
                        detail="Jumlah transaksi diterima"
                    />
                    <SummaryItem
                        label="Rata-rata transaksi"
                        value={formatCurrency(report.revenue.average, currency)}
                        detail="Nilai rata-rata per pembayaran"
                    />
                </div>

                <div className="mt-4 grid overflow-hidden rounded-lg border sm:grid-cols-2">
                    <SummaryItem
                        label="Revenue membership"
                        value={formatCurrency(
                            report.revenue.membership_total,
                            currency,
                        )}
                        detail="Membership lunas"
                    />
                    <SummaryItem
                        label="Revenue Personal Training"
                        value={formatCurrency(
                            report.revenue.pt_total,
                            currency,
                        )}
                        detail="Paket PT lunas"
                    />
                </div>

                <div className="mt-4">
                    <p className="text-xs font-medium text-muted-foreground">
                        Metode pembayaran
                    </p>
                    {report.revenue.method_breakdown.length === 0 ? (
                        <EmptyLine text="Belum ada pembayaran lunas pada periode ini." />
                    ) : (
                        <div className="mt-2 divide-y border-y">
                            {report.revenue.method_breakdown.map((method) => (
                                <div
                                    key={method.method ?? method.label}
                                    className="grid grid-cols-[1fr_auto] gap-4 py-3 text-sm"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {method.label}
                                        </p>
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            {method.payment_count} pembayaran
                                        </p>
                                    </div>
                                    <p className="self-center font-semibold tabular-nums">
                                        {formatCurrency(method.total, currency)}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </section>

            <section
                className="border-t pt-8"
                aria-labelledby="pt-report-heading"
            >
                <SectionHeading
                    id="pt-report-heading"
                    icon={Dumbbell}
                    title="Personal Training"
                    description="Penjualan paket, realisasi sesi, dan beban trainer."
                />
                <div className="mt-4 grid overflow-hidden rounded-lg border sm:grid-cols-2 lg:grid-cols-3">
                    <SummaryItem
                        label="Klien aktif"
                        value={String(report.personal_training.active_clients)}
                        detail="Paket aktif per tanggal acuan"
                    />
                    <SummaryItem
                        label="Paket terjual"
                        value={String(report.personal_training.packages_sold)}
                        detail="Dibeli pada periode laporan"
                    />
                    <SummaryItem
                        label="Revenue PT"
                        value={formatCurrency(
                            report.personal_training.revenue,
                            currency,
                        )}
                        detail="Pembayaran PT lunas"
                    />
                    <SummaryItem
                        label="Sesi selesai"
                        value={String(
                            report.personal_training.completed_sessions,
                        )}
                        detail="Selesai pada periode laporan"
                    />
                    <SummaryItem
                        label="Sesi mendatang"
                        value={String(
                            report.personal_training.upcoming_sessions,
                        )}
                        detail="Masih terjadwal"
                    />
                    <SummaryItem
                        label="No-show"
                        value={String(report.personal_training.no_shows)}
                        detail="Tercatat pada periode laporan"
                    />
                </div>
                <div className="mt-5 overflow-hidden rounded-lg border">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-muted/50 text-xs text-muted-foreground">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Trainer
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Klien aktif
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Selesai
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Mendatang
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {report.personal_training.trainers.map(
                                (trainer) => (
                                    <tr key={trainer.id}>
                                        <td className="px-4 py-3">
                                            <p className="font-medium">
                                                {trainer.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {trainer.trainer_code ?? '-'}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {trainer.active_clients}
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {trainer.completed_sessions}
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {trainer.upcoming_sessions}
                                        </td>
                                    </tr>
                                ),
                            )}
                        </tbody>
                    </table>
                    {report.personal_training.trainers.length === 0 && (
                        <p className="p-5 text-sm text-muted-foreground">
                            Belum ada trainer aktif.
                        </p>
                    )}
                </div>
            </section>

            <div className="grid gap-8 border-t pt-8 xl:grid-cols-2">
                <section aria-labelledby="member-report-heading">
                    <SectionHeading
                        id="member-report-heading"
                        icon={Users}
                        title="Member"
                        description="Status akun saat ini dan pendaftaran baru."
                    />
                    <div className="mt-4 grid overflow-hidden rounded-lg border sm:grid-cols-3">
                        <SummaryItem
                            label="Aktif"
                            value={String(report.members.active)}
                            detail="Status akun aktif"
                        />
                        <SummaryItem
                            label="Nonaktif"
                            value={String(report.members.inactive)}
                            detail="Status akun nonaktif"
                        />
                        <SummaryItem
                            label="Member baru"
                            value={String(report.members.new_in_period)}
                            detail={`Terdaftar dalam ${range.days} hari`}
                        />
                    </div>
                </section>

                <section aria-labelledby="membership-report-heading">
                    <SectionHeading
                        id="membership-report-heading"
                        icon={CalendarClock}
                        title="Membership"
                        description="Status periode per tanggal akhir laporan."
                    />
                    <div className="mt-4 grid overflow-hidden rounded-lg border sm:grid-cols-2">
                        <SummaryItem
                            label="Aktif"
                            value={String(report.memberships.active)}
                            detail="Membership valid pada tanggal acuan"
                        />
                        <SummaryItem
                            label="Berakhir"
                            value={String(report.memberships.expired)}
                            detail="Belum memiliki periode aktif baru"
                        />
                        <SummaryItem
                            label="Segera berakhir"
                            value={String(report.memberships.expiring_soon)}
                            detail={`${report.memberships.warning_days} hari setelah tanggal acuan`}
                        />
                        <SummaryItem
                            label="Mulai pada periode ini"
                            value={String(report.memberships.started_in_period)}
                            detail="Record membership baru berjalan"
                        />
                    </div>
                </section>
            </div>

            <section
                className="border-t pt-8"
                aria-labelledby="check-in-report-heading"
            >
                <SectionHeading
                    id="check-in-report-heading"
                    icon={UserRoundCheck}
                    title="Check-in"
                    description="Volume kunjungan dan member paling rutin."
                />
                <div className="mt-4 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,0.8fr)]">
                    <div className="grid h-fit overflow-hidden rounded-lg border sm:grid-cols-3">
                        <SummaryItem
                            label="Total kunjungan"
                            value={String(report.check_ins.total)}
                            detail="Seluruh check-in periode ini"
                        />
                        <SummaryItem
                            label="Member unik"
                            value={String(report.check_ins.unique_members)}
                            detail="Member yang berkunjung"
                        />
                        <SummaryItem
                            label="Rata-rata harian"
                            value={report.check_ins.daily_average}
                            detail="Check-in per hari"
                        />
                    </div>

                    <div>
                        <p className="text-xs font-medium text-muted-foreground">
                            Kunjungan terbanyak
                        </p>
                        {report.check_ins.top_visitors.length === 0 ? (
                            <EmptyLine text="Belum ada check-in pada periode ini." />
                        ) : (
                            <ol className="mt-2 divide-y border-y">
                                {report.check_ins.top_visitors.map(
                                    (visitor, index) => (
                                        <li
                                            key={visitor.member.id}
                                            className="flex items-center gap-3 py-3"
                                        >
                                            <span className="flex size-7 shrink-0 items-center justify-center rounded-md bg-muted text-xs font-semibold text-muted-foreground">
                                                {index + 1}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <Link
                                                    href={MemberController.show(
                                                        visitor.member.id,
                                                    )}
                                                    className="block truncate text-sm font-medium hover:text-primary"
                                                >
                                                    {visitor.member.name}
                                                </Link>
                                                <p className="mt-0.5 truncate font-mono text-xs text-muted-foreground">
                                                    {
                                                        visitor.member
                                                            .member_number
                                                    }
                                                </p>
                                            </div>
                                            <p className="shrink-0 text-sm font-semibold tabular-nums">
                                                {visitor.visit_count} kali
                                            </p>
                                        </li>
                                    ),
                                )}
                            </ol>
                        )}
                    </div>
                </div>
            </section>
        </div>
    );
}

function SectionHeading({
    id,
    icon: Icon,
    title,
    description,
}: {
    id: string;
    icon: ComponentType<{ className?: string }>;
    title: string;
    description: string;
}) {
    return (
        <div className="flex items-start gap-3">
            <div className="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                <Icon className="size-4" />
            </div>
            <div>
                <h2 id={id} className="text-base font-semibold">
                    {title}
                </h2>
                <p className="mt-0.5 text-xs text-muted-foreground">
                    {description}
                </p>
            </div>
        </div>
    );
}

function SummaryItem({
    label,
    value,
    detail,
}: {
    label: string;
    value: string;
    detail: string;
}) {
    return (
        <article className="border-b p-4 last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0">
            <p className="text-xs font-medium text-muted-foreground">{label}</p>
            <p className="mt-2 truncate text-xl font-semibold tabular-nums">
                {value}
            </p>
            <p className="mt-1 text-xs leading-5 text-muted-foreground">
                {detail}
            </p>
        </article>
    );
}

function EmptyLine({ text }: { text: string }) {
    return (
        <div className="mt-2 border-y py-6 text-center text-xs text-muted-foreground">
            {text}
        </div>
    );
}

function ReportSkeleton() {
    return (
        <div className="space-y-8" aria-label="Memuat laporan">
            {[3, 3, 4, 3].map((columns, section) => (
                <div
                    key={section}
                    className={section > 0 ? 'border-t pt-8' : ''}
                >
                    <Skeleton className="h-5 w-40" />
                    <Skeleton className="mt-2 h-3 w-64 max-w-full" />
                    <div className="mt-4 grid overflow-hidden rounded-lg border sm:grid-cols-3">
                        {Array.from({ length: columns }, (_, index) => (
                            <div key={index} className="p-4">
                                <Skeleton className="h-3 w-24" />
                                <Skeleton className="mt-3 h-7 w-20" />
                                <Skeleton className="mt-2 h-3 w-32" />
                            </div>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}

function ReportError({ reloading }: { reloading: boolean }) {
    return (
        <div className="flex min-h-64 flex-col items-center justify-center border-y px-6 py-12 text-center">
            <ChartNoAxesCombined className="size-9 text-muted-foreground" />
            <p className="mt-4 text-sm font-medium">
                Data laporan belum dapat dimuat
            </p>
            <p className="mt-1 text-xs text-muted-foreground">
                Coba ulangi tanpa mengubah filter aktif.
            </p>
            <Button
                type="button"
                variant="outline"
                size="sm"
                className="mt-4"
                disabled={reloading}
                onClick={() => router.reload({ only: ['report'] })}
            >
                <RefreshCw className={reloading ? 'animate-spin' : ''} />
                Coba lagi
            </Button>
        </div>
    );
}

function formatRange(range: ReportRange, timezone: string): string {
    if (range.date_from === range.date_to) {
        return formatDate(range.date_from, timezone);
    }

    return `${formatDate(range.date_from, timezone)} – ${formatDate(range.date_to, timezone)}`;
}

ReportsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Laporan',
            href: reportsIndex(),
        },
    ],
};
