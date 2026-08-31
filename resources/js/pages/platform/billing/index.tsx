import { Form, Head, Link } from '@inertiajs/react';
import { Banknote, Clock3, FileCheck2, History, Settings2 } from 'lucide-react';
import PlatformBillingController from '@/actions/App/Http/Controllers/PlatformBillingController';
import PlatformSubscriptionPaymentController from '@/actions/App/Http/Controllers/PlatformSubscriptionPaymentController';
import InputError from '@/components/input-error';
import { PlatformStatusBadge } from '@/components/platform/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency, formatDate, formatDateTime } from '@/lib/formatters';
import { index } from '@/routes/platform/billing';
import { show as userShow } from '@/routes/platform/users';
import { proof as paymentProof } from '@/routes/subscription-payments';

type Payment = {
    id: number;
    subscriber_id: number;
    subscriber_name: string;
    subscriber_email: string;
    plan_name: string;
    amount: string;
    currency: string;
    billing_interval_label: string;
    reference_number: string;
    status: 'pending' | 'approved' | 'rejected';
    status_label: string;
    submitted_at: string;
    reviewed_at: string | null;
    reviewer_name: string | null;
    review_notes: string | null;
    period_ends_at: string | null;
};

type Props = {
    activeTab: 'pending' | 'history' | 'settings';
    settings: {
        bank_name: string | null;
        account_name: string | null;
        account_number: string | null;
        instructions: string | null;
        is_configured: boolean;
        updated_at: string | null;
        updated_by: string | null;
    };
    payments: {
        data: Payment[];
        from: number | null;
        to: number | null;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
};

const tabs = [
    { key: 'pending', label: 'Perlu approval', icon: Clock3 },
    { key: 'history', label: 'Riwayat', icon: History },
    { key: 'settings', label: 'Rekening pembayaran', icon: Settings2 },
] as const;

export default function PlatformBillingIndex({
    activeTab,
    settings,
    payments,
}: Props) {
    return (
        <>
            <Head title="Billing Subscription" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Billing platform
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold">
                            Pembayaran subscription
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Atur rekening tujuan dan verifikasi transfer
                            subscriber dari satu tempat.
                        </p>
                    </div>
                    <PlatformStatusBadge
                        status={settings.is_configured ? 'active' : 'pending'}
                        label={
                            settings.is_configured
                                ? 'Rekening siap'
                                : 'Rekening belum diatur'
                        }
                    />
                </header>

                <nav
                    aria-label="Navigasi billing"
                    className="flex gap-1 overflow-x-auto rounded-xl border bg-muted/30 p-1"
                >
                    {tabs.map(({ key, label, icon: Icon }) => (
                        <Link
                            key={key}
                            href={index({ query: { tab: key } })}
                            preserveScroll
                            className={`flex shrink-0 items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors ${
                                activeTab === key
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            <Icon className="size-4" /> {label}
                        </Link>
                    ))}
                </nav>

                {activeTab === 'settings' ? (
                    <BillingSettings settings={settings} />
                ) : (
                    <PaymentList
                        payments={payments}
                        pending={activeTab === 'pending'}
                    />
                )}
            </div>
        </>
    );
}

function BillingSettings({ settings }: { settings: Props['settings'] }) {
    return (
        <section className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div className="rounded-xl border p-5">
                <div className="flex items-start gap-3">
                    <Banknote className="mt-0.5 size-5 text-muted-foreground" />
                    <div>
                        <h2 className="font-semibold">
                            Rekening transfer subscriber
                        </h2>
                        <p className="mt-1 text-sm leading-6 text-muted-foreground">
                            Informasi ini langsung tampil pada tab Pembayaran
                            milik Owner.
                        </p>
                    </div>
                </div>

                <Form
                    {...PlatformBillingController.update.form()}
                    options={{ preserveScroll: true }}
                    className="mt-6 grid gap-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="bank_name">Nama bank</Label>
                                    <Input
                                        id="bank_name"
                                        name="bank_name"
                                        defaultValue={settings.bank_name ?? ''}
                                        required
                                        maxLength={120}
                                        placeholder="Contoh: BCA"
                                    />
                                    <InputError message={errors.bank_name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="account_number">
                                        Nomor rekening
                                    </Label>
                                    <Input
                                        id="account_number"
                                        name="account_number"
                                        defaultValue={
                                            settings.account_number ?? ''
                                        }
                                        required
                                        maxLength={100}
                                        inputMode="numeric"
                                        placeholder="Contoh: 1234567890"
                                    />
                                    <InputError
                                        message={errors.account_number}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="account_name">
                                    Nama pemilik rekening
                                </Label>
                                <Input
                                    id="account_name"
                                    name="account_name"
                                    defaultValue={settings.account_name ?? ''}
                                    required
                                    maxLength={120}
                                    placeholder="Contoh: PT GymFlow Indonesia"
                                />
                                <InputError message={errors.account_name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="instructions">
                                    Instruksi tambahan
                                </Label>
                                <textarea
                                    id="instructions"
                                    name="instructions"
                                    defaultValue={settings.instructions ?? ''}
                                    rows={4}
                                    maxLength={1000}
                                    placeholder="Opsional, misalnya format berita transfer."
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                />
                                <InputError message={errors.instructions} />
                            </div>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="justify-self-start"
                            >
                                {processing
                                    ? 'Menyimpan...'
                                    : 'Simpan rekening'}
                            </Button>
                        </>
                    )}
                </Form>
            </div>

            <aside className="rounded-xl border bg-muted/30 p-5">
                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Preview Owner
                </p>
                <h2 className="mt-2 font-semibold">Tujuan transfer</h2>
                {settings.is_configured ? (
                    <dl className="mt-5 grid gap-4 text-sm">
                        <Info label="Bank" value={settings.bank_name ?? '-'} />
                        <Info
                            label="Nomor rekening"
                            value={settings.account_number ?? '-'}
                        />
                        <Info
                            label="Atas nama"
                            value={settings.account_name ?? '-'}
                        />
                    </dl>
                ) : (
                    <p className="mt-4 text-sm leading-6 text-muted-foreground">
                        Lengkapi tiga data wajib agar Owner dapat melakukan
                        transfer.
                    </p>
                )}
                {settings.updated_at && (
                    <p className="mt-6 border-t pt-4 text-xs text-muted-foreground">
                        Terakhir diperbarui{' '}
                        {formatDateTime(settings.updated_at)}
                        {settings.updated_by
                            ? ` oleh ${settings.updated_by}`
                            : ''}
                        .
                    </p>
                )}
            </aside>
        </section>
    );
}

function PaymentList({
    payments,
    pending,
}: {
    payments: Props['payments'];
    pending: boolean;
}) {
    if (payments.data.length === 0) {
        return (
            <section className="flex min-h-64 flex-col items-center justify-center rounded-xl border border-dashed p-8 text-center">
                <FileCheck2 className="size-7 text-muted-foreground" />
                <h2 className="mt-4 font-semibold">
                    {pending
                        ? 'Tidak ada pembayaran yang menunggu'
                        : 'Belum ada riwayat review'}
                </h2>
                <p className="mt-2 text-sm text-muted-foreground">
                    {pending
                        ? 'Semua bukti transfer sudah ditangani.'
                        : 'Pembayaran yang disetujui atau ditolak akan tampil di sini.'}
                </p>
            </section>
        );
    }

    return (
        <section className="overflow-hidden rounded-xl border">
            <div className="divide-y">
                {payments.data.map((payment) => (
                    <article
                        key={payment.id}
                        className="grid gap-5 p-5 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.75fr)]"
                    >
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <p className="font-semibold">
                                    {formatCurrency(
                                        payment.amount,
                                        payment.currency,
                                    )}
                                </p>
                                <PlatformStatusBadge
                                    status={payment.status}
                                    label={payment.status_label}
                                />
                            </div>
                            <Link
                                href={userShow(payment.subscriber_id)}
                                className="mt-2 inline-block text-sm font-medium hover:underline"
                            >
                                {payment.subscriber_name}
                            </Link>
                            <p className="text-xs text-muted-foreground">
                                {payment.subscriber_email}
                            </p>
                            <p className="mt-3 text-sm text-muted-foreground">
                                {payment.plan_name} · {payment.reference_number}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Dikirim {formatDateTime(payment.submitted_at)}
                            </p>
                            {payment.review_notes && (
                                <p className="mt-3 rounded-lg bg-muted/50 p-3 text-sm text-muted-foreground">
                                    {payment.review_notes}
                                </p>
                            )}
                            <Button
                                variant="outline"
                                size="sm"
                                className="mt-4"
                                asChild
                            >
                                <Link href={paymentProof(payment.id)}>
                                    <FileCheck2 /> Lihat bukti transfer
                                </Link>
                            </Button>
                        </div>

                        {payment.status === 'pending' ? (
                            <Form
                                {...PlatformSubscriptionPaymentController.form(
                                    payment.id,
                                )}
                                options={{ preserveScroll: true }}
                                className="grid content-start gap-3 rounded-lg bg-muted/40 p-4"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor={`decision-${payment.id}`}
                                            >
                                                Keputusan
                                            </Label>
                                            <select
                                                id={`decision-${payment.id}`}
                                                name="decision"
                                                defaultValue="approved"
                                                className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            >
                                                <option value="approved">
                                                    Setujui pembayaran
                                                </option>
                                                <option value="rejected">
                                                    Tolak pembayaran
                                                </option>
                                            </select>
                                            <InputError
                                                message={errors.decision}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor={`review_notes-${payment.id}`}
                                            >
                                                Catatan review
                                            </Label>
                                            <textarea
                                                id={`review_notes-${payment.id}`}
                                                name="review_notes"
                                                rows={3}
                                                maxLength={1000}
                                                placeholder="Wajib jika pembayaran ditolak."
                                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                            />
                                            <InputError
                                                message={errors.review_notes}
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="justify-self-start"
                                        >
                                            {processing
                                                ? 'Memproses...'
                                                : 'Simpan keputusan'}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        ) : (
                            <div className="rounded-lg bg-muted/40 p-4 text-sm text-muted-foreground">
                                <p>
                                    Ditinjau{' '}
                                    {payment.reviewed_at
                                        ? formatDateTime(payment.reviewed_at)
                                        : '-'}
                                    {payment.reviewer_name
                                        ? ` oleh ${payment.reviewer_name}`
                                        : ''}
                                    .
                                </p>
                                {payment.period_ends_at && (
                                    <p className="mt-2">
                                        Akses aktif sampai{' '}
                                        {formatDate(payment.period_ends_at)}.
                                    </p>
                                )}
                            </div>
                        )}
                    </article>
                ))}
            </div>
            <Pagination payments={payments} />
        </section>
    );
}

function Pagination({ payments }: { payments: Props['payments'] }) {
    if (payments.links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-xs text-muted-foreground">
                Menampilkan {payments.from ?? 0}-{payments.to ?? 0} dari{' '}
                {payments.total}
            </p>
            <div className="flex flex-wrap gap-1">
                {payments.links.map((link, indexValue) =>
                    link.url ? (
                        <Button
                            key={`${link.label}-${indexValue}`}
                            variant={link.active ? 'default' : 'outline'}
                            size="sm"
                            asChild
                        >
                            <Link
                                href={link.url}
                                preserveScroll
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        </Button>
                    ) : (
                        <Button
                            key={`${link.label}-${indexValue}`}
                            variant="outline"
                            size="sm"
                            disabled
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ),
                )}
            </div>
        </div>
    );
}

function Info({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="mt-1 font-medium tabular-nums">{value}</dd>
        </div>
    );
}

PlatformBillingIndex.layout = {
    breadcrumbs: [{ title: 'Billing Subscription', href: index() }],
};
