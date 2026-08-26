import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Banknote,
    ChevronLeft,
    ChevronRight,
    CircleDollarSign,
    Dumbbell,
    FilterX,
    Search,
    Tickets,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import { PaymentStatusBadge } from '@/components/payments/payment-status-badge';
import { RecordPaymentDialog } from '@/components/payments/record-payment-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate, formatDateTime } from '@/lib/formatters';
import type { PaymentIndexProps, PaymentListItem, PaymentType } from '@/types';

const selectClassName =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export default function PaymentsIndex({
    payments,
    filters,
    summary,
    statusOptions,
    methodOptions,
}: PaymentIndexProps) {
    const { auth } = usePage().props;
    const currency = auth.currentGym?.currency ?? 'IDR';
    const timezone = auth.currentGym?.timezone ?? 'Asia/Jakarta';
    const activeType: PaymentType =
        filters.type === 'personal_training'
            ? 'personal_training'
            : 'membership';
    const activeLabel =
        activeType === 'membership' ? 'Membership' : 'Personal Trainer';
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [method, setMethod] = useState(filters.method);
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const hasFilters =
        filters.search !== '' ||
        filters.status !== '' ||
        filters.method !== '' ||
        filters.date_from !== '' ||
        filters.date_to !== '';

    function submitFilters(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get(
            PaymentController.index.url(),
            {
                type: activeType,
                search,
                status,
                method,
                date_from: dateFrom,
                date_to: dateTo,
            },
            { preserveState: true, replace: true },
        );
    }

    function clearFilters() {
        setSearch('');
        setStatus('');
        setMethod('');
        setDateFrom('');
        setDateTo('');
        router.get(
            PaymentController.index.url(),
            { type: activeType },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Pembayaran" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-5 px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
                <header>
                    <h1 className="text-2xl font-semibold tracking-normal">
                        Pembayaran
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Kelola invoice membership dan layanan PT.
                    </p>
                </header>

                <nav
                    role="tablist"
                    aria-label="Jenis pembayaran"
                    className="grid grid-cols-2 border-b"
                >
                    <PaymentTab
                        type="membership"
                        activeType={activeType}
                        label="Membership"
                        icon={<Tickets />}
                    />
                    <PaymentTab
                        type="personal_training"
                        activeType={activeType}
                        label="Personal Trainer"
                        icon={<Dumbbell />}
                    />
                </nav>

                <section
                    className="grid gap-3 sm:grid-cols-2"
                    aria-label={`Ringkasan pembayaran ${activeLabel}`}
                >
                    <SummaryItem
                        label="Sudah diterima"
                        value={formatCurrency(summary.paid_total, currency)}
                        detail={`${summary.paid_count} invoice lunas`}
                    />
                    <SummaryItem
                        label="Menunggu pembayaran"
                        value={formatCurrency(
                            summary.outstanding_total,
                            currency,
                        )}
                        detail={`${summary.pending_count} invoice belum lunas`}
                    />
                </section>

                <form
                    onSubmit={submitFilters}
                    className="grid gap-3 rounded-lg border p-3 sm:grid-cols-2 xl:grid-cols-[minmax(12rem,1fr)_9rem_10rem_minmax(14rem,auto)_auto]"
                    aria-label={`Filter pembayaran ${activeLabel}`}
                >
                    <div className="relative sm:col-span-2 xl:col-span-1">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari invoice atau member"
                            className="pl-9"
                            aria-label="Cari pembayaran"
                        />
                    </div>
                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className={selectClassName}
                        aria-label="Filter status pembayaran"
                    >
                        <option value="">Semua status</option>
                        {statusOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={method}
                        onChange={(event) => setMethod(event.target.value)}
                        className={selectClassName}
                        aria-label="Filter metode pembayaran"
                    >
                        <option value="">Semua metode</option>
                        {methodOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <div className="grid min-w-0 grid-cols-2 gap-2">
                        <Input
                            type="date"
                            value={dateFrom}
                            onChange={(event) =>
                                setDateFrom(event.target.value)
                            }
                            aria-label="Tanggal invoice mulai"
                            className="min-w-0"
                        />
                        <Input
                            type="date"
                            value={dateTo}
                            min={dateFrom || undefined}
                            onChange={(event) => setDateTo(event.target.value)}
                            aria-label="Tanggal invoice akhir"
                            className="min-w-0"
                        />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" className="flex-1 xl:flex-none">
                            <Search />
                            Terapkan
                        </Button>
                        {hasFilters && (
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                onClick={clearFilters}
                                title="Hapus filter"
                            >
                                <FilterX />
                                <span className="sr-only">Hapus filter</span>
                            </Button>
                        )}
                    </div>
                </form>

                <section
                    id={`payment-panel-${activeType}`}
                    role="tabpanel"
                    aria-labelledby={`payment-tab-${activeType}`}
                    tabIndex={0}
                    className="outline-none"
                >
                    {payments.data.length === 0 ? (
                        <div className="flex min-h-56 flex-col items-center justify-center rounded-lg border px-6 py-10 text-center">
                            <div className="flex size-10 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                <CircleDollarSign className="size-5" />
                            </div>
                            <p className="mt-4 text-sm font-medium">
                                {hasFilters
                                    ? 'Pembayaran tidak ditemukan'
                                    : `Belum ada invoice ${activeLabel}`}
                            </p>
                            <p className="mt-1 max-w-sm text-xs leading-5 text-muted-foreground">
                                {hasFilters
                                    ? 'Coba ubah atau hapus filter pencarian.'
                                    : 'Invoice akan tampil setelah transaksi dibuat.'}
                            </p>
                            {hasFilters && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="mt-4"
                                    onClick={clearFilters}
                                >
                                    <FilterX />
                                    Hapus filter
                                </Button>
                            )}
                        </div>
                    ) : (
                        <>
                            <div className="hidden overflow-x-auto rounded-lg border xl:block">
                                <table className="w-full min-w-[920px] text-left text-sm">
                                    <thead className="border-b bg-muted/50 text-xs text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">
                                                Invoice
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Member
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {activeType === 'membership'
                                                    ? 'Paket membership'
                                                    : 'Paket PT'}
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium">
                                                Nilai
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Status
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {payments.data.map((payment) => (
                                            <PaymentTableRow
                                                key={payment.id}
                                                payment={payment}
                                                currency={currency}
                                                timezone={timezone}
                                                methodOptions={methodOptions}
                                            />
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="grid gap-3 xl:hidden">
                                {payments.data.map((payment) => (
                                    <PaymentCard
                                        key={payment.id}
                                        payment={payment}
                                        currency={currency}
                                        timezone={timezone}
                                        methodOptions={methodOptions}
                                    />
                                ))}
                            </div>
                        </>
                    )}
                </section>

                {payments.last_page > 1 && (
                    <nav
                        className="flex items-center justify-between gap-4 border-t pt-4"
                        aria-label="Pagination pembayaran"
                    >
                        <p className="hidden text-sm text-muted-foreground sm:block">
                            {payments.from}-{payments.to} dari {payments.total}
                        </p>
                        <div className="flex w-full items-center justify-between gap-1 sm:w-auto sm:justify-end">
                            <PaginationButton
                                url={payments.links[0]?.url ?? null}
                                label="Sebelumnya"
                                icon={<ChevronLeft />}
                            />
                            <div className="hidden gap-1 sm:flex">
                                {payments.links.slice(1, -1).map((link) => (
                                    <Button
                                        key={link.label}
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        size="icon"
                                        disabled={link.url === null}
                                        asChild={link.url !== null}
                                    >
                                        {link.url ? (
                                            <Link
                                                href={link.url}
                                                preserveScroll
                                            >
                                                {link.label}
                                            </Link>
                                        ) : (
                                            <span>{link.label}</span>
                                        )}
                                    </Button>
                                ))}
                            </div>
                            <PaginationButton
                                url={payments.links.at(-1)?.url ?? null}
                                label="Berikutnya"
                                icon={<ChevronRight />}
                                iconAfter
                            />
                        </div>
                    </nav>
                )}
            </div>
        </>
    );
}

function PaymentTab({
    type,
    activeType,
    label,
    icon,
}: {
    type: PaymentType;
    activeType: PaymentType;
    label: string;
    icon: ReactNode;
}) {
    const isActive = type === activeType;

    return (
        <Link
            id={`payment-tab-${type}`}
            href={PaymentController.index({ query: { type } })}
            role="tab"
            aria-selected={isActive}
            aria-controls={`payment-panel-${type}`}
            className={[
                'flex min-h-11 items-center justify-center gap-2 border-b-2 px-3 py-2.5 text-sm font-medium transition-colors [&>svg]:size-4',
                isActive
                    ? 'border-primary text-foreground'
                    : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
            ].join(' ')}
            prefetch
        >
            {icon}
            {label}
        </Link>
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
        <div className="rounded-lg border p-4">
            <p className="text-xs font-medium text-muted-foreground">{label}</p>
            <p className="mt-2 text-xl font-semibold tabular-nums">{value}</p>
            <p className="mt-1 text-xs text-muted-foreground">{detail}</p>
        </div>
    );
}

function PaymentTableRow({
    payment,
    currency,
    timezone,
    methodOptions,
}: {
    payment: PaymentListItem;
    currency: string;
    timezone: string;
    methodOptions: PaymentIndexProps['methodOptions'];
}) {
    return (
        <tr className="hover:bg-muted/30">
            <td className="px-4 py-3">
                <p className="font-mono text-xs font-medium">
                    {payment.invoice_number}
                </p>
                <p className="mt-1 text-xs text-muted-foreground">
                    {formatDateTime(payment.created_at, timezone)}
                </p>
            </td>
            <td className="px-4 py-3">
                <Link
                    href={MemberController.show(payment.member.id)}
                    className="font-medium hover:text-primary"
                >
                    {payment.member.name}
                </Link>
                <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                    {payment.member.member_number}
                </p>
            </td>
            <td className="px-4 py-3">
                <p className="font-medium">{sourceName(payment)}</p>
                <p className="mt-0.5 text-xs text-muted-foreground">
                    {sourcePeriod(payment)}
                </p>
            </td>
            <td className="px-4 py-3 text-right font-semibold tabular-nums">
                {formatCurrency(payment.amount, currency)}
            </td>
            <td className="px-4 py-3">
                <PaymentStatusBadge
                    status={payment.status}
                    label={payment.status_label}
                />
                <p className="mt-1 text-xs text-muted-foreground">
                    {payment.status === 'paid'
                        ? `${payment.method_label} · ${formatDateTime(payment.paid_at, timezone)}`
                        : 'Menunggu pembayaran'}
                </p>
            </td>
            <td className="px-4 py-3 text-right">
                {payment.status === 'pending' ? (
                    <RecordPaymentDialog
                        payment={payment}
                        currency={currency}
                        methodOptions={methodOptions}
                        compact
                    />
                ) : (
                    <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                        <Banknote className="size-3.5" />
                        {payment.received_by?.name ?? 'Tercatat'}
                    </span>
                )}
            </td>
        </tr>
    );
}

function PaymentCard({
    payment,
    currency,
    timezone,
    methodOptions,
}: {
    payment: PaymentListItem;
    currency: string;
    timezone: string;
    methodOptions: PaymentIndexProps['methodOptions'];
}) {
    return (
        <article className="rounded-lg border p-4">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate font-mono text-xs font-medium">
                        {payment.invoice_number}
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {formatDateTime(payment.created_at, timezone)}
                    </p>
                </div>
                <PaymentStatusBadge
                    status={payment.status}
                    label={payment.status_label}
                />
            </div>

            <Link
                href={MemberController.show(payment.member.id)}
                className="mt-4 block truncate text-sm font-medium hover:text-primary"
            >
                {payment.member.name}
            </Link>
            <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                {payment.member.member_number}
            </p>

            <div className="mt-4 grid grid-cols-[minmax(0,1fr)_auto] gap-4 border-t pt-4">
                <div className="min-w-0">
                    <p className="truncate text-sm font-medium">
                        {sourceName(payment)}
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {sourcePeriod(payment)}
                    </p>
                </div>
                <p className="text-right text-sm font-semibold tabular-nums">
                    {formatCurrency(payment.amount, currency)}
                </p>
            </div>

            <div className="mt-4 flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-xs text-muted-foreground">
                    {payment.status === 'paid'
                        ? `${payment.method_label} · ${formatDateTime(payment.paid_at, timezone)}`
                        : 'Menunggu pembayaran'}
                </p>
                {payment.status === 'pending' ? (
                    <RecordPaymentDialog
                        payment={payment}
                        currency={currency}
                        methodOptions={methodOptions}
                    />
                ) : (
                    <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                        <Banknote className="size-3.5" />
                        {payment.received_by?.name ?? 'Tercatat'}
                    </span>
                )}
            </div>
        </article>
    );
}

function sourceName(payment: PaymentListItem): string {
    return (
        payment.membership?.plan_name ??
        payment.personal_training?.package_name ??
        '-'
    );
}

function sourcePeriod(payment: PaymentListItem): string {
    if (payment.membership) {
        return `${formatDate(payment.membership.start_date)} · ${formatDate(payment.membership.end_date)}`;
    }

    if (payment.personal_training) {
        return `${payment.personal_training.total_sessions} sesi · ${payment.personal_training.trainer_name}`;
    }

    return '-';
}

function PaginationButton({
    url,
    label,
    icon,
    iconAfter = false,
}: {
    url: string | null;
    label: string;
    icon: ReactNode;
    iconAfter?: boolean;
}) {
    return (
        <Button
            variant="outline"
            size="sm"
            disabled={!url}
            asChild={Boolean(url)}
        >
            {url ? (
                <Link href={url} preserveScroll>
                    {!iconAfter && icon}
                    {label}
                    {iconAfter && icon}
                </Link>
            ) : (
                <span>
                    {!iconAfter && icon}
                    {label}
                    {iconAfter && icon}
                </span>
            )}
        </Button>
    );
}

PaymentsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Pembayaran',
            href: PaymentController.index({
                query: { type: 'membership' },
            }),
        },
    ],
};
