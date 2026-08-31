import { Form, Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Banknote,
    Building2,
    Check,
    CircleAlert,
    Clock3,
    FileCheck2,
    Upload,
    UsersRound,
    WalletCards,
} from 'lucide-react';
import type { ReactNode } from 'react';
import SubscriptionPaymentController from '@/actions/App/Http/Controllers/SubscriptionPaymentController';
import InputError from '@/components/input-error';
import { PlatformStatusBadge } from '@/components/platform/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency, formatDate, formatDateTime } from '@/lib/formatters';
import { show } from '@/routes/subscription';
import { proof as paymentProof } from '@/routes/subscription-payments';
import type { SubscriptionPayment } from '@/types';

type Plan = {
    id: number;
    name: string;
    description: string | null;
    price: string;
    currency: string;
    billing_interval_label: string;
    max_gyms: number | null;
    max_members: number | null;
    max_staff: number | null;
    is_current: boolean;
    can_select: boolean;
};

type Props = {
    gym: {
        id: number;
        name: string;
        status: string;
        status_label: string;
    };
    activeTab: 'overview' | 'plans' | 'payment' | 'history';
    plans: Plan[];
    selectedPlan: Pick<
        Plan,
        | 'id'
        | 'name'
        | 'price'
        | 'currency'
        | 'billing_interval_label'
        | 'can_select'
    > | null;
    canSubmitPayment: boolean;
    subscription: {
        id: number;
        status: string;
        status_label: string;
        plan_name: string;
        price: string;
        currency: string;
        billing_interval_label: string;
        usage: { gyms: number; members: number; staff: number };
        limits: {
            gyms: number | null;
            members: number | null;
            staff: number | null;
        };
        trial_ends_at: string | null;
        current_period_starts_at: string | null;
        current_period_ends_at: string | null;
        grants_access: boolean;
        has_pending_payment: boolean;
        payments: SubscriptionPayment[];
    } | null;
    billing: {
        bank_name: string | null;
        account_name: string | null;
        account_number: string | null;
        instructions: string | null;
        is_configured: boolean;
    };
};

const tabs = [
    { key: 'overview', label: 'Ringkasan' },
    { key: 'plans', label: 'Upgrade paket' },
    { key: 'payment', label: 'Pembayaran' },
    { key: 'history', label: 'Riwayat' },
] as const;

export default function SubscriptionShow(props: Props) {
    const { gym, subscription, activeTab } = props;
    const pendingPayment = subscription?.payments.find(
        (payment) => payment.status === 'pending',
    );
    const currentPlan = props.plans.find((plan) => plan.is_current);

    return (
        <>
            <Head title="Subscription" />
            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Subscription akun Owner
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold">
                            Paket & pembayaran
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Satu subscription berlaku untuk seluruh gym milik
                            akun ini.
                        </p>
                    </div>
                    {subscription && (
                        <div className="flex flex-wrap gap-2">
                            <PlatformStatusBadge
                                status={gym.status}
                                label={`Gym ${gym.status_label}`}
                            />
                            <PlatformStatusBadge
                                status={subscription.status}
                                label={subscription.status_label}
                            />
                        </div>
                    )}
                </header>

                {subscription && !subscription.grants_access && (
                    <div className="flex flex-col gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-amber-900 dark:bg-amber-950/30">
                        <div className="flex items-start gap-3">
                            <CircleAlert className="mt-0.5 size-5 shrink-0 text-amber-700 dark:text-amber-300" />
                            <div>
                                <p className="text-sm font-semibold text-amber-950 dark:text-amber-100">
                                    {pendingPayment
                                        ? 'Pembayaran sedang diverifikasi'
                                        : 'Masa aktif subscription berakhir'}
                                </p>
                                <p className="mt-1 text-sm text-amber-800 dark:text-amber-200">
                                    {pendingPayment
                                        ? 'Menu operasional aktif kembali setelah Platform Admin menyetujui pembayaran.'
                                        : 'Perpanjang paket untuk mengaktifkan kembali menu operasional.'}
                                </p>
                            </div>
                        </div>
                        {!pendingPayment && currentPlan && (
                            <Button asChild>
                                <Link
                                    href={show({
                                        query: {
                                            tab: 'payment',
                                            plan_id: currentPlan.id,
                                        },
                                    })}
                                >
                                    Perpanjang sekarang <ArrowRight />
                                </Link>
                            </Button>
                        )}
                    </div>
                )}

                <nav
                    aria-label="Navigasi subscription"
                    className="flex gap-1 overflow-x-auto rounded-xl border bg-muted/30 p-1"
                >
                    {tabs.map((tab) => (
                        <Link
                            key={tab.key}
                            href={show({ query: { tab: tab.key } })}
                            preserveScroll
                            className={`shrink-0 rounded-lg px-4 py-2 text-sm font-medium transition-colors ${
                                activeTab === tab.key
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            {tab.label}
                        </Link>
                    ))}
                </nav>

                {subscription === null ? (
                    <EmptyState
                        title="Subscription belum tersedia"
                        description="Hubungi Platform Admin agar subscription akun Owner dapat dipulihkan."
                    />
                ) : activeTab === 'overview' ? (
                    <Overview subscription={subscription} />
                ) : activeTab === 'plans' ? (
                    <Plans plans={props.plans} />
                ) : activeTab === 'payment' ? (
                    <Payment
                        billing={props.billing}
                        plan={props.selectedPlan}
                        pendingPayment={pendingPayment}
                        canSubmitPayment={props.canSubmitPayment}
                    />
                ) : (
                    <History payments={subscription.payments} />
                )}
            </div>
        </>
    );
}

function Overview({
    subscription,
}: {
    subscription: NonNullable<Props['subscription']>;
}) {
    return (
        <section className="overflow-hidden rounded-xl border">
            <div className="flex flex-col gap-4 border-b p-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div className="flex items-center gap-2">
                        <WalletCards className="size-5 text-muted-foreground" />
                        <h2 className="text-lg font-semibold">
                            {subscription.plan_name}
                        </h2>
                    </div>
                    <p className="mt-3 text-2xl font-semibold tabular-nums">
                        {formatCurrency(
                            subscription.price,
                            subscription.currency,
                        )}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        per {subscription.billing_interval_label.toLowerCase()}
                    </p>
                </div>
                <Button variant="outline" asChild>
                    <Link href={show({ query: { tab: 'plans' } })}>
                        Lihat pilihan paket <ArrowRight />
                    </Link>
                </Button>
            </div>
            <dl className="grid gap-5 p-5 sm:grid-cols-3">
                <Info
                    label="Trial berakhir"
                    value={formatDate(subscription.trial_ends_at)}
                />
                <Info
                    label="Periode dimulai"
                    value={formatDate(subscription.current_period_starts_at)}
                />
                <Info
                    label="Periode berakhir"
                    value={formatDate(subscription.current_period_ends_at)}
                />
            </dl>
            <div className="grid gap-3 border-t p-5 sm:grid-cols-3">
                <Capacity
                    icon={Building2}
                    label="Gym"
                    usage={subscription.usage.gyms}
                    limit={subscription.limits.gyms}
                />
                <Capacity
                    icon={UsersRound}
                    label="Member"
                    usage={subscription.usage.members}
                    limit={subscription.limits.members}
                />
                <Capacity
                    icon={UsersRound}
                    label="Staf"
                    usage={subscription.usage.staff}
                    limit={subscription.limits.staff}
                />
            </div>
        </section>
    );
}

function Plans({ plans }: { plans: Plan[] }) {
    return (
        <section>
            <div className="mb-4">
                <h2 className="text-lg font-semibold">Pilih paket</h2>
                <p className="mt-1 text-sm text-muted-foreground">
                    Pilih paket berbayar, lalu lanjutkan ke instruksi transfer.
                </p>
            </div>
            <div className="grid gap-4 lg:grid-cols-3">
                {plans.map((plan) => {
                    const isFree = Number(plan.price) === 0;

                    return (
                        <article
                            key={plan.id}
                            className={`flex flex-col rounded-xl border p-5 ${
                                plan.is_current
                                    ? 'border-primary bg-primary/5'
                                    : ''
                            }`}
                        >
                            <div className="flex items-start justify-between gap-3">
                                <h3 className="font-semibold">{plan.name}</h3>
                                {plan.is_current && (
                                    <span className="rounded-full bg-primary px-2.5 py-1 text-xs font-medium text-primary-foreground">
                                        Paket saat ini
                                    </span>
                                )}
                            </div>
                            <p className="mt-3 text-2xl font-semibold tabular-nums">
                                {isFree
                                    ? 'Gratis'
                                    : formatCurrency(plan.price, plan.currency)}
                            </p>
                            {!isFree && (
                                <p className="text-xs text-muted-foreground">
                                    per{' '}
                                    {plan.billing_interval_label.toLowerCase()}
                                </p>
                            )}
                            <p className="mt-4 min-h-10 text-sm leading-5 text-muted-foreground">
                                {plan.description}
                            </p>
                            <ul className="mt-4 grid gap-2 text-sm">
                                <Limit label="Gym" value={plan.max_gyms} />
                                <Limit
                                    label="Member"
                                    value={plan.max_members}
                                />
                                <Limit label="Staf" value={plan.max_staff} />
                            </ul>
                            <div className="mt-5">
                                {isFree ? (
                                    <Button
                                        variant="outline"
                                        className="w-full"
                                        disabled
                                    >
                                        {plan.is_current
                                            ? 'Sedang digunakan'
                                            : 'Paket Free'}
                                    </Button>
                                ) : plan.can_select ? (
                                    <Button className="w-full" asChild>
                                        <Link
                                            href={show({
                                                query: {
                                                    tab: 'payment',
                                                    plan_id: plan.id,
                                                },
                                            })}
                                        >
                                            {plan.is_current
                                                ? 'Perpanjang paket'
                                                : 'Pilih paket'}{' '}
                                            <ArrowRight />
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        variant="outline"
                                        className="w-full"
                                        disabled
                                    >
                                        Kapasitas tidak mencukupi
                                    </Button>
                                )}
                            </div>
                        </article>
                    );
                })}
            </div>
        </section>
    );
}

function Payment({
    billing,
    plan,
    pendingPayment,
    canSubmitPayment,
}: {
    billing: Props['billing'];
    plan: Props['selectedPlan'];
    pendingPayment: SubscriptionPayment | undefined;
    canSubmitPayment: boolean;
}) {
    if (pendingPayment) {
        return (
            <EmptyState
                icon={Clock3}
                title="Menunggu persetujuan Platform Admin"
                description={`Pembayaran ${pendingPayment.plan_name} dengan referensi ${pendingPayment.reference_number} dikirim ${formatDateTime(pendingPayment.submitted_at)}.`}
            />
        );
    }

    if (!plan || Number(plan.price) === 0) {
        return (
            <EmptyState
                title="Pilih paket berbayar terlebih dahulu"
                description="Buka tab Upgrade paket untuk menentukan paket yang ingin diaktifkan."
                action={
                    <Button asChild>
                        <Link href={show({ query: { tab: 'plans' } })}>
                            Pilih paket <ArrowRight />
                        </Link>
                    </Button>
                }
            />
        );
    }

    if (!billing.is_configured) {
        return (
            <EmptyState
                icon={Banknote}
                title="Rekening pembayaran sedang disiapkan"
                description="Platform Admin belum menyimpan rekening tujuan. Anda belum perlu melakukan transfer; silakan coba kembali setelah rekening tersedia."
            />
        );
    }

    return (
        <section className="grid gap-5 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <div className="rounded-xl border p-5">
                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Langkah 1 · Transfer
                </p>
                <h2 className="mt-2 text-lg font-semibold">{plan.name}</h2>
                <p className="mt-1 text-2xl font-semibold tabular-nums">
                    {formatCurrency(plan.price, plan.currency)}
                </p>
                <dl className="mt-5 grid gap-4 rounded-lg bg-muted/50 p-4 text-sm">
                    <Info label="Bank" value={billing.bank_name ?? '-'} />
                    <Info
                        label="Nomor rekening"
                        value={billing.account_number ?? '-'}
                    />
                    <Info
                        label="Atas nama"
                        value={billing.account_name ?? '-'}
                    />
                </dl>
                {billing.instructions && (
                    <p className="mt-4 text-sm leading-6 text-muted-foreground">
                        {billing.instructions}
                    </p>
                )}
            </div>

            <div className="rounded-xl border p-5">
                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Langkah 2 · Konfirmasi
                </p>
                <h2 className="mt-2 text-lg font-semibold">
                    Kirim bukti transfer
                </h2>
                <p className="mt-1 text-sm text-muted-foreground">
                    Paket aktif setelah bukti diverifikasi Platform Admin.
                </p>
                {canSubmitPayment ? (
                    <Form
                        {...SubscriptionPaymentController.store.form()}
                        resetOnSuccess
                        className="mt-5 grid gap-4"
                    >
                        {({ errors, processing, progress }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="saas_plan_id"
                                    value={plan.id}
                                />
                                <InputError message={errors.saas_plan_id} />
                                <div className="grid gap-2">
                                    <Label htmlFor="reference_number">
                                        Nomor referensi transfer
                                    </Label>
                                    <Input
                                        id="reference_number"
                                        name="reference_number"
                                        required
                                        maxLength={100}
                                        placeholder="Contoh: TRX123456789"
                                    />
                                    <InputError
                                        message={errors.reference_number}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="proof">
                                        Bukti transfer
                                    </Label>
                                    <Input
                                        id="proof"
                                        name="proof"
                                        type="file"
                                        required
                                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        JPG, PNG, WebP, atau PDF. Maksimal 5 MB.
                                    </p>
                                    <InputError message={errors.proof} />
                                </div>
                                {progress && (
                                    <div className="h-1.5 overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="h-full bg-primary transition-all"
                                            style={{
                                                width: `${progress.percentage}%`,
                                            }}
                                        />
                                    </div>
                                )}
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="justify-self-start"
                                >
                                    <Upload />{' '}
                                    {processing
                                        ? 'Mengunggah...'
                                        : 'Kirim untuk approval'}
                                </Button>
                            </>
                        )}
                    </Form>
                ) : (
                    <p className="mt-5 rounded-lg bg-muted/50 p-4 text-sm text-muted-foreground">
                        Paket ini tidak dapat diproses untuk pemakaian akun saat
                        ini.
                    </p>
                )}
            </div>
        </section>
    );
}

function History({ payments }: { payments: SubscriptionPayment[] }) {
    if (payments.length === 0) {
        return (
            <EmptyState
                title="Belum ada riwayat pembayaran"
                description="Pembayaran manual yang Anda kirim akan tampil di sini."
            />
        );
    }

    return (
        <section className="divide-y overflow-hidden rounded-xl border">
            {payments.map((payment) => (
                <article
                    key={payment.id}
                    className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <p className="font-medium">{payment.plan_name}</p>
                            <PlatformStatusBadge
                                status={payment.status}
                                label={payment.status_label}
                            />
                        </div>
                        <p className="mt-2 text-sm text-muted-foreground">
                            {formatCurrency(payment.amount, payment.currency)} ·{' '}
                            {payment.reference_number}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Dikirim {formatDateTime(payment.submitted_at)}
                            {payment.reviewed_at
                                ? ` · Ditinjau ${formatDateTime(payment.reviewed_at)}`
                                : ''}
                        </p>
                        {payment.review_notes && (
                            <p className="mt-2 text-sm text-muted-foreground">
                                Catatan: {payment.review_notes}
                            </p>
                        )}
                    </div>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={paymentProof(payment.id)}>
                            <FileCheck2 /> Lihat bukti
                        </Link>
                    </Button>
                </article>
            ))}
        </section>
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

function Capacity({
    icon: Icon,
    label,
    usage,
    limit,
}: {
    icon: typeof Building2;
    label: string;
    usage: number;
    limit: number | null;
}) {
    return (
        <div className="rounded-lg bg-muted/50 p-4">
            <Icon className="size-4 text-muted-foreground" />
            <p className="mt-3 text-lg font-semibold tabular-nums">
                {usage} / {limit ?? '∞'}
            </p>
            <p className="text-sm text-muted-foreground">{label} digunakan</p>
        </div>
    );
}

function Limit({ label, value }: { label: string; value: number | null }) {
    return (
        <li className="flex items-center gap-2">
            <Check className="size-4 text-primary" />
            <span>
                {value ?? 'Tanpa batas'} {label.toLowerCase()}
            </span>
        </li>
    );
}

function EmptyState({
    title,
    description,
    icon: Icon = CircleAlert,
    action,
}: {
    title: string;
    description: string;
    icon?: typeof CircleAlert;
    action?: ReactNode;
}) {
    return (
        <section className="flex min-h-64 flex-col items-center justify-center rounded-xl border border-dashed p-8 text-center">
            <div className="rounded-full bg-muted p-3">
                <Icon className="size-6 text-muted-foreground" />
            </div>
            <h2 className="mt-4 font-semibold">{title}</h2>
            <p className="mt-2 max-w-lg text-sm leading-6 text-muted-foreground">
                {description}
            </p>
            {action && <div className="mt-5">{action}</div>}
        </section>
    );
}

SubscriptionShow.layout = {
    breadcrumbs: [{ title: 'Subscription', href: show() }],
};
