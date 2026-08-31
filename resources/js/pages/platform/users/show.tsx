import { Form, Head, Link } from '@inertiajs/react';
import {
    Building2,
    CreditCard,
    FileCheck2,
    ShieldCheck,
    UsersRound,
    WalletCards,
} from 'lucide-react';
import PlatformSubscriptionPaymentController from '@/actions/App/Http/Controllers/PlatformSubscriptionPaymentController';
import PlatformUserStatusController from '@/actions/App/Http/Controllers/PlatformUserStatusController';
import PlatformUserSubscriptionController from '@/actions/App/Http/Controllers/PlatformUserSubscriptionController';
import InputError from '@/components/input-error';
import { PlatformStatusBadge } from '@/components/platform/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { formatCurrency, formatDate, formatDateTime } from '@/lib/formatters';
import { index } from '@/routes/platform/users';
import { proof as paymentProof } from '@/routes/subscription-payments';
import type { PlatformUser, SelectOption } from '@/types';

type PlanOption = {
    id: number;
    name: string;
    price: string;
    currency: string;
    billing_interval_label: string;
};

const controlClassName =
    'h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export default function PlatformUserShow({
    managedUser,
    plans,
    subscriptionStatusOptions,
}: {
    managedUser: PlatformUser;
    plans: PlanOption[];
    subscriptionStatusOptions: SelectOption[];
}) {
    const subscription = managedUser.subscription;
    const subscriptionPayments = subscription?.payments ?? [];

    return (
        <>
            <Head title={managedUser.name} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <Link
                            href={index()}
                            className="text-sm text-muted-foreground hover:text-foreground"
                        >
                            ← Kembali ke pengguna
                        </Link>
                        <h1 className="mt-3 text-2xl font-semibold">
                            {managedUser.name}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {managedUser.email}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Badge variant="outline">
                            {managedUser.account_type_label}
                        </Badge>
                        <PlatformStatusBadge
                            status={
                                managedUser.is_active ? 'active' : 'suspended'
                            }
                            label={managedUser.is_active ? 'Aktif' : 'Nonaktif'}
                        />
                    </div>
                </header>

                <section className="grid gap-3 sm:grid-cols-3">
                    <Metric
                        icon={Building2}
                        label="Gym terhubung"
                        value={managedUser.gyms_count}
                    />
                    <Metric
                        icon={ShieldCheck}
                        label="Gym sebagai Owner"
                        value={managedUser.owned_gyms_count}
                    />
                    <Metric
                        icon={WalletCards}
                        label="Paket"
                        value={subscription?.plan_name ?? 'Tidak ada'}
                    />
                </section>

                <div className="grid gap-6 xl:grid-cols-2">
                    <section className="rounded-xl border p-5">
                        <h2 className="font-semibold">Akses akun</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Menonaktifkan akun memutus login ke seluruh gym yang
                            terhubung tanpa menghapus riwayat data.
                        </p>
                        {managedUser.can_update_status ? (
                            <Form
                                {...PlatformUserStatusController.form(
                                    managedUser.id,
                                )}
                                className="mt-5 grid gap-4"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="is_active">
                                                Status akses
                                            </Label>
                                            <select
                                                id="is_active"
                                                name="is_active"
                                                defaultValue={
                                                    managedUser.is_active
                                                        ? '1'
                                                        : '0'
                                                }
                                                className={controlClassName}
                                            >
                                                <option value="1">Aktif</option>
                                                <option value="0">
                                                    Nonaktif
                                                </option>
                                            </select>
                                            <InputError
                                                message={errors.is_active}
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="justify-self-start"
                                        >
                                            {processing
                                                ? 'Menyimpan...'
                                                : 'Simpan akses'}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        ) : (
                            <p className="mt-5 rounded-lg bg-muted/50 p-4 text-sm text-muted-foreground">
                                Akun Platform Super Admin dilindungi dan tidak
                                dapat dinonaktifkan dari halaman ini.
                            </p>
                        )}
                    </section>

                    <section className="rounded-xl border p-5">
                        <h2 className="font-semibold">
                            Subscription subscriber
                        </h2>
                        {subscription &&
                        subscription.pending_payments_count === 0 ? (
                            <Form
                                {...PlatformUserSubscriptionController.update.form(
                                    managedUser.id,
                                )}
                                className="mt-5 grid gap-4"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="saas_plan_id">
                                                Paket
                                            </Label>
                                            <select
                                                id="saas_plan_id"
                                                name="saas_plan_id"
                                                defaultValue={
                                                    subscription.plan_id
                                                }
                                                className={controlClassName}
                                            >
                                                {plans.map((plan) => (
                                                    <option
                                                        key={plan.id}
                                                        value={plan.id}
                                                    >
                                                        {plan.name} ·{' '}
                                                        {formatCurrency(
                                                            plan.price,
                                                            plan.currency,
                                                        )}{' '}
                                                        /{' '}
                                                        {plan.billing_interval_label.toLowerCase()}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.saas_plan_id}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="status">
                                                Status subscription
                                            </Label>
                                            <select
                                                id="status"
                                                name="status"
                                                defaultValue={
                                                    subscription.status
                                                }
                                                className={controlClassName}
                                            >
                                                {subscriptionStatusOptions.map(
                                                    (option) => (
                                                        <option
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            <InputError
                                                message={errors.status}
                                            />
                                        </div>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <DateTimeField
                                                name="trial_ends_at"
                                                label="Trial berakhir"
                                                value={
                                                    subscription.trial_ends_at
                                                }
                                                error={errors.trial_ends_at}
                                            />
                                            <DateTimeField
                                                name="current_period_starts_at"
                                                label="Periode dimulai"
                                                value={
                                                    subscription.current_period_starts_at
                                                }
                                                error={
                                                    errors.current_period_starts_at
                                                }
                                            />
                                            <DateTimeField
                                                name="current_period_ends_at"
                                                label="Periode berakhir"
                                                value={
                                                    subscription.current_period_ends_at
                                                }
                                                error={
                                                    errors.current_period_ends_at
                                                }
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="justify-self-start"
                                        >
                                            {processing
                                                ? 'Menyimpan...'
                                                : 'Simpan subscription'}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        ) : subscription ? (
                            <p className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                                Ada pembayaran yang menunggu approval. Review
                                pembayaran di bawah sebelum mengubah paket,
                                status, atau periode subscription.
                            </p>
                        ) : (
                            <p className="mt-4 rounded-lg bg-muted/50 p-4 text-sm text-muted-foreground">
                                Akun ini adalah staf gym dan tidak memiliki
                                subscription sendiri. Paket dikelola melalui
                                akun Owner subscriber.
                            </p>
                        )}
                    </section>
                </div>

                {subscription?.usage && subscription.limits && (
                    <section className="rounded-xl border p-5">
                        <h2 className="font-semibold">Pemakaian paket</h2>
                        <div className="mt-4 grid gap-3 sm:grid-cols-3">
                            <Usage
                                label="Gym"
                                used={subscription.usage.gyms}
                                limit={subscription.limits.gyms}
                            />
                            <Usage
                                label="Member"
                                used={subscription.usage.members}
                                limit={subscription.limits.members}
                            />
                            <Usage
                                label="Staf"
                                used={subscription.usage.staff}
                                limit={subscription.limits.staff}
                            />
                        </div>
                    </section>
                )}

                {subscription && (
                    <section className="overflow-hidden rounded-xl border">
                        <div className="flex items-start gap-3 border-b p-5">
                            <CreditCard className="mt-0.5 size-5 text-muted-foreground" />
                            <div>
                                <h2 className="font-semibold">
                                    Pembayaran subscription
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Verifikasi bukti transfer subscriber. Hanya
                                    approval yang akan mengaktifkan dan
                                    memperpanjang periode paket.
                                </p>
                            </div>
                        </div>
                        {subscriptionPayments.length === 0 ? (
                            <p className="p-5 text-sm text-muted-foreground">
                                Belum ada pembayaran subscription.
                            </p>
                        ) : (
                            <div className="divide-y">
                                {subscriptionPayments.map((payment) => (
                                    <div
                                        key={payment.id}
                                        className="grid gap-5 p-5 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.8fr)]"
                                    >
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-medium">
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
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                {payment.plan_name} ·{' '}
                                                {payment.reference_number}
                                            </p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Dikirim{' '}
                                                {formatDateTime(
                                                    payment.submitted_at,
                                                )}
                                                {payment.reviewer_name
                                                    ? ` · Ditinjau ${payment.reviewer_name}`
                                                    : ''}
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
                                                <Link
                                                    href={paymentProof(
                                                        payment.id,
                                                    )}
                                                >
                                                    <FileCheck2 /> Lihat bukti
                                                </Link>
                                            </Button>
                                        </div>

                                        {payment.status === 'pending' ? (
                                            <Form
                                                {...PlatformSubscriptionPaymentController.form(
                                                    payment.id,
                                                )}
                                                options={{
                                                    preserveScroll: true,
                                                }}
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
                                                                className={
                                                                    controlClassName
                                                                }
                                                            >
                                                                <option value="approved">
                                                                    Setujui
                                                                    pembayaran
                                                                </option>
                                                                <option value="rejected">
                                                                    Tolak
                                                                    pembayaran
                                                                </option>
                                                            </select>
                                                            <InputError
                                                                message={
                                                                    errors.decision
                                                                }
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
                                                                placeholder="Wajib diisi jika pembayaran ditolak."
                                                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.review_notes
                                                                }
                                                            />
                                                        </div>
                                                        <Button
                                                            type="submit"
                                                            disabled={
                                                                processing
                                                            }
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
                                                {payment.period_ends_at
                                                    ? `Akses diperpanjang sampai ${formatDate(payment.period_ends_at)}.`
                                                    : 'Pembayaran sudah selesai ditinjau.'}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                )}

                <section className="rounded-xl border">
                    <div className="border-b p-5">
                        <h2 className="font-semibold">Gym & role</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Registrasi mandiri membuat user sebagai Owner gym
                            pertama. Role staf tetap terpisah per gym.
                        </p>
                    </div>
                    {(managedUser.gyms ?? []).length === 0 ? (
                        <p className="p-5 text-sm text-muted-foreground">
                            Akun belum terhubung ke gym.
                        </p>
                    ) : (
                        <div className="divide-y">
                            {(managedUser.gyms ?? []).map((gym) => (
                                <div
                                    key={gym.id}
                                    className="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p className="text-sm font-medium">
                                            {gym.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {gym.slug} · {gym.role_label}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                            <UsersRound className="size-3.5" />
                                            {gym.members_count} member ·{' '}
                                            {gym.staff_count} staf
                                        </span>
                                        <Badge variant="outline">
                                            {gym.access_status === 'active'
                                                ? 'Akses aktif'
                                                : 'Akses nonaktif'}
                                        </Badge>
                                        <PlatformStatusBadge
                                            status={gym.status}
                                            label={gym.status_label}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                    <p className="border-t px-5 py-3 text-xs text-muted-foreground">
                        Terdaftar {formatDate(managedUser.created_at)} · email{' '}
                        {managedUser.email_verified_at
                            ? `terverifikasi ${formatDateTime(managedUser.email_verified_at)}`
                            : 'belum terverifikasi'}
                    </p>
                </section>
            </div>
        </>
    );
}

function Metric({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof Building2;
    label: string;
    value: string | number;
}) {
    return (
        <div className="rounded-xl border p-4">
            <Icon className="size-4 text-muted-foreground" />
            <p className="mt-3 text-xl font-semibold tabular-nums">{value}</p>
            <p className="text-sm text-muted-foreground">{label}</p>
        </div>
    );
}

function Usage({
    label,
    used,
    limit,
}: {
    label: string;
    used: number;
    limit: number | null;
}) {
    return (
        <div className="rounded-lg bg-muted/50 p-4">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="mt-1 text-lg font-semibold tabular-nums">
                {used} / {limit ?? '∞'}
            </p>
        </div>
    );
}

function DateTimeField({
    name,
    label,
    value,
    error,
}: {
    name: string;
    label: string;
    value: string | null | undefined;
    error?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={name}>{label}</Label>
            <input
                id={name}
                name={name}
                type="datetime-local"
                defaultValue={value ? value.slice(0, 16) : ''}
                className={controlClassName}
            />
            <InputError message={error} />
        </div>
    );
}

PlatformUserShow.layout = {
    breadcrumbs: [
        { title: 'Pengguna', href: index() },
        { title: 'Detail pengguna', href: '#' },
    ],
};
