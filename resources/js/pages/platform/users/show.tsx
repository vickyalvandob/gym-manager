import { Form, Head, Link } from '@inertiajs/react';
import { Building2, ShieldCheck, WalletCards } from 'lucide-react';
import PlatformUserStatusController from '@/actions/App/Http/Controllers/PlatformUserStatusController';
import PlatformUserSubscriptionController from '@/actions/App/Http/Controllers/PlatformUserSubscriptionController';
import InputError from '@/components/input-error';
import { PlatformStatusBadge } from '@/components/platform/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { formatCurrency, formatDate, formatDateTime } from '@/lib/formatters';
import { show as gymShow } from '@/routes/platform/gyms';
import { index } from '@/routes/platform/users';
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
                        {subscription ? (
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
                                <Link
                                    key={gym.id}
                                    href={gymShow(gym.id)}
                                    className="flex flex-col gap-2 p-4 transition-colors hover:bg-muted/30 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p className="text-sm font-medium">
                                            {gym.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {gym.slug} · {gym.role_label}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
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
                                </Link>
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
