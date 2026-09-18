import { Deferred, Head } from '@inertiajs/react';
import { CreditCard, ReceiptText, WalletCards } from 'lucide-react';
import {
    PortalDataError,
    PortalDataSkeleton,
} from '@/components/member-portal/portal-deferred-state';
import { PortalPageHeader } from '@/components/member-portal/portal-page-header';
import { PortalPagination } from '@/components/member-portal/portal-pagination';
import { PaymentStatusBadge } from '@/components/payments/payment-status-badge';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDateTime } from '@/lib/formatters';
import type { MemberPortalPaymentsProps } from '@/types';

export default function MemberPortalPayments({
    member,
    payments,
}: MemberPortalPaymentsProps) {
    const timezone = member.gym.timezone;
    const currency = member.gym.currency;

    return (
        <div className="mx-auto max-w-5xl space-y-7">
            <Head title="Invoice & Pembayaran" />
            <PortalPageHeader
                eyebrow="Keuangan"
                title="Invoice & pembayaran"
                description="Pantau tagihan membership dan Personal Training beserta status pembayarannya."
            />

            <Deferred
                data="payments"
                fallback={<PortalDataSkeleton />}
                rescue={({ reloading }) => (
                    <PortalDataError
                        reloading={reloading}
                        only={['payments']}
                    />
                )}
            >
                {payments && (
                    <section className="overflow-hidden rounded-xl border bg-background">
                        <div className="flex items-start gap-3 border-b px-5 py-5 sm:px-6">
                            <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <ReceiptText className="size-4" />
                            </span>
                            <div>
                                <h2 className="text-base font-semibold">
                                    Daftar invoice
                                </h2>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    Total {payments.total} invoice tercatat
                                </p>
                            </div>
                        </div>

                        {payments.data.length === 0 ? (
                            <div className="px-6 py-16 text-center">
                                <WalletCards className="mx-auto size-9 text-muted-foreground" />
                                <p className="mt-4 text-sm font-semibold">
                                    Belum ada invoice
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Invoice baru dari Front Office akan tampil
                                    di sini.
                                </p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {payments.data.map((payment) => (
                                    <article
                                        key={payment.id}
                                        className="grid gap-4 px-5 py-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-6"
                                    >
                                        <div className="flex min-w-0 items-start gap-3">
                                            <span className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                                <CreditCard className="size-4" />
                                            </span>
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="font-mono text-xs font-semibold">
                                                        {payment.invoice_number}
                                                    </p>
                                                    <Badge variant="outline">
                                                        {payment.type_label}
                                                    </Badge>
                                                </div>
                                                <p className="mt-1 truncate text-sm font-medium">
                                                    {payment.description}
                                                </p>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {payment.method_label ??
                                                        'Metode belum dipilih'}{' '}
                                                    ·{' '}
                                                    {formatDateTime(
                                                        payment.paid_at ??
                                                            payment.created_at,
                                                        timezone,
                                                    )}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex items-center justify-between gap-4 pl-12 sm:flex-col sm:items-end sm:pl-0">
                                            <p className="text-sm font-semibold tabular-nums">
                                                {formatCurrency(
                                                    payment.amount,
                                                    currency,
                                                )}
                                            </p>
                                            <PaymentStatusBadge
                                                status={payment.status}
                                                label={payment.status_label}
                                            />
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}

                        <PortalPagination paginator={payments} />
                    </section>
                )}
            </Deferred>
        </div>
    );
}
