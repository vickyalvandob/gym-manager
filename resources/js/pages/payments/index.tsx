import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Banknote,
    ChevronLeft,
    ChevronRight,
    CircleDollarSign,
    FilterX,
    Search,
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
import type { PaymentIndexProps, PaymentListItem } from '@/types';

export default function PaymentsIndex({
    payments,
    filters,
    summary,
    statusOptions,
    methodOptions,
    typeOptions,
}: PaymentIndexProps) {
    const { auth } = usePage().props;
    const currency = auth.currentGym?.currency ?? 'IDR';
    const timezone = auth.currentGym?.timezone ?? 'Asia/Jakarta';
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [method, setMethod] = useState(filters.method);
    const [type, setType] = useState(filters.type);
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [perPage, setPerPage] = useState(String(filters.per_page));
    const hasFilters =
        filters.search !== '' ||
        filters.status !== '' ||
        filters.method !== '' ||
        filters.type !== '' ||
        filters.date_from !== '' ||
        filters.date_to !== '';

    function submitFilters(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get(
            PaymentController.index.url(),
            {
                search,
                status,
                method,
                type,
                date_from: dateFrom,
                date_to: dateTo,
                per_page: perPage,
            },
            { preserveState: true, replace: true },
        );
    }

    function clearFilters() {
        setSearch('');
        setStatus('');
        setMethod('');
        setType('');
        setDateFrom('');
        setDateTo('');
        setPerPage('15');
        router.get(
            PaymentController.index.url(),
            {},
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Pembayaran" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">
                        {payments.total} invoice
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                        Pembayaran
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Riwayat penerimaan membership dan Personal Training.
                    </p>
                </header>

                <section
                    className="grid overflow-hidden rounded-lg border sm:grid-cols-2 lg:grid-cols-4"
                    aria-label="Ringkasan pembayaran"
                >
                    <SummaryItem
                        label="Penerimaan"
                        value={formatCurrency(summary.paid_total, currency)}
                        detail={`${summary.paid_count} invoice lunas`}
                    />
                    <SummaryItem
                        label="Belum diterima"
                        value={formatCurrency(
                            summary.outstanding_total,
                            currency,
                        )}
                        detail={`${summary.pending_count} invoice pending`}
                    />
                    <SummaryItem
                        label="Pendapatan membership"
                        value={formatCurrency(
                            summary.membership_revenue,
                            currency,
                        )}
                        detail="Invoice membership lunas"
                    />
                    <SummaryItem
                        label="Pendapatan PT"
                        value={formatCurrency(summary.pt_revenue, currency)}
                        detail="Invoice Personal Training lunas"
                    />
                </section>

                <form
                    onSubmit={submitFilters}
                    className="grid gap-3 border-y py-4 md:grid-cols-2 xl:grid-cols-[minmax(14rem,1fr)_10rem_11rem_11rem_10rem_10rem_8rem_auto]"
                    aria-label="Filter pembayaran"
                >
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Invoice, member, atau telepon"
                            className="pl-9"
                            aria-label="Cari pembayaran"
                        />
                    </div>
                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
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
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        aria-label="Filter metode pembayaran"
                    >
                        <option value="">Semua metode</option>
                        {methodOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={type}
                        onChange={(event) => setType(event.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        aria-label="Filter jenis pembayaran"
                    >
                        <option value="">Semua jenis</option>
                        {typeOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <Input
                        type="date"
                        value={dateFrom}
                        onChange={(event) => setDateFrom(event.target.value)}
                        aria-label="Tanggal invoice mulai"
                    />
                    <Input
                        type="date"
                        value={dateTo}
                        min={dateFrom || undefined}
                        onChange={(event) => setDateTo(event.target.value)}
                        aria-label="Tanggal invoice akhir"
                    />
                    <select
                        value={perPage}
                        onChange={(event) => setPerPage(event.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        aria-label="Jumlah per halaman"
                    >
                        {[10, 15, 25, 50].map((value) => (
                            <option key={value} value={value}>
                                {value} / halaman
                            </option>
                        ))}
                    </select>
                    <div className="flex gap-2">
                        <Button
                            type="submit"
                            variant="outline"
                            className="flex-1"
                        >
                            <Search />
                            Terapkan
                        </Button>
                        {hasFilters && (
                            <Button
                                type="button"
                                variant="ghost"
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

                {payments.data.length === 0 ? (
                    <div className="flex min-h-64 flex-col items-center justify-center border-y px-6 py-12 text-center">
                        <div className="flex size-10 items-center justify-center rounded-md bg-muted text-muted-foreground">
                            <CircleDollarSign className="size-5" />
                        </div>
                        <p className="mt-4 text-sm font-medium">
                            {hasFilters
                                ? 'Pembayaran tidak ditemukan'
                                : 'Belum ada invoice pembayaran'}
                        </p>
                        <p className="mt-1 max-w-sm text-xs leading-5 text-muted-foreground">
                            Invoice dibuat otomatis saat membership atau paket
                            PT diaktifkan.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="hidden overflow-hidden rounded-lg border lg:block">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/50 text-xs text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Invoice
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Member
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Membership
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Nilai
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Pembayaran
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

                        <div className="divide-y border-y lg:hidden">
                            {payments.data.map((payment) => (
                                <article key={payment.id} className="py-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="truncate font-mono text-sm font-medium">
                                                {payment.invoice_number}
                                            </p>
                                            <Link
                                                href={MemberController.show(
                                                    payment.member.id,
                                                )}
                                                className="mt-1 block truncate text-sm hover:text-primary"
                                            >
                                                {payment.member.name} ·{' '}
                                                {payment.member.member_number}
                                            </Link>
                                        </div>
                                        <PaymentStatusBadge
                                            status={payment.status}
                                            label={payment.status_label}
                                        />
                                    </div>
                                    <div className="mt-3 grid grid-cols-2 gap-4 text-xs">
                                        <div>
                                            <p className="text-muted-foreground">
                                                Paket
                                            </p>
                                            <p className="mt-1 font-medium">
                                                {sourceName(payment)}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-muted-foreground">
                                                Nilai
                                            </p>
                                            <p className="mt-1 font-semibold tabular-nums">
                                                {formatCurrency(
                                                    payment.amount,
                                                    currency,
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                    <p className="mt-3 text-xs text-muted-foreground">
                                        {payment.status === 'paid'
                                            ? `${payment.method_label} · ${formatDateTime(payment.paid_at, timezone)}`
                                            : `Dibuat ${formatDateTime(payment.created_at, timezone)}`}
                                    </p>
                                    {payment.status === 'pending' && (
                                        <div className="mt-3">
                                            <RecordPaymentDialog
                                                payment={payment}
                                                currency={currency}
                                                methodOptions={methodOptions}
                                            />
                                        </div>
                                    )}
                                </article>
                            ))}
                        </div>
                    </>
                )}

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
        <div className="border-b p-4 last:border-b-0 sm:border-r lg:border-b-0 lg:last:border-r-0 sm:[&:nth-child(2)]:border-r-0 lg:[&:nth-child(2)]:border-r">
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
                    {payment.type_label} · {sourcePeriod(payment)}
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
            href: PaymentController.index(),
        },
    ],
};
