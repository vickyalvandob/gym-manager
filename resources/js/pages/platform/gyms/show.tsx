import { Form, Head, Link } from '@inertiajs/react';
import { Building2, CalendarDays, UsersRound } from 'lucide-react';
import PlatformGymStatusController from '@/actions/App/Http/Controllers/PlatformGymStatusController';
import PlatformSubscriptionController from '@/actions/App/Http/Controllers/PlatformSubscriptionController';
import InputError from '@/components/input-error';
import { PlatformStatusBadge } from '@/components/platform/status-badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { formatDate, formatCurrency } from '@/lib/formatters';
import { index } from '@/routes/platform/gyms';
import { show as userShow } from '@/routes/platform/users';
import type { PlatformGym, SelectOption } from '@/types';

type PlanOption = {
    id: number;
    name: string;
    price: string;
    currency: string;
    billing_interval_label: string;
};

type Props = {
    gym: PlatformGym;
    plans: PlanOption[];
    gymStatusOptions: SelectOption[];
    subscriptionStatusOptions: SelectOption[];
};

const controlClassName =
    'h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function PlatformGymShow({
    gym,
    plans,
    gymStatusOptions,
    subscriptionStatusOptions,
}: Props) {
    const subscription = gym.subscription;

    return (
        <>
            <Head title={gym.name} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <Link
                            href={index()}
                            className="text-sm text-muted-foreground hover:text-foreground"
                        >
                            ← Kembali ke tenant gym
                        </Link>
                        <h1 className="mt-3 text-2xl font-semibold">
                            {gym.name}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {gym.slug}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <PlatformStatusBadge
                            status={gym.status}
                            label={gym.status_label}
                        />
                        {subscription && (
                            <PlatformStatusBadge
                                status={subscription.status}
                                label={subscription.status_label}
                            />
                        )}
                    </div>
                </div>

                <section className="grid gap-3 sm:grid-cols-3">
                    <Metric
                        icon={UsersRound}
                        label="Pengguna"
                        value={gym.users_count}
                    />
                    <Metric
                        icon={Building2}
                        label="Member"
                        value={gym.members_count}
                    />
                    <Metric
                        icon={CalendarDays}
                        label="Trainer"
                        value={gym.trainers_count}
                    />
                </section>

                <div className="grid gap-6 xl:grid-cols-2">
                    <section className="rounded-xl border p-5">
                        <h2 className="font-semibold">Akses gym</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Suspension memutus akses operasional seluruh
                            pengguna gym.
                        </p>
                        <Form
                            {...PlatformGymStatusController.form(gym.id)}
                            className="mt-5 grid gap-4"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="gym_status">
                                            Status gym
                                        </Label>
                                        <select
                                            id="gym_status"
                                            name="status"
                                            defaultValue={gym.status}
                                            className={controlClassName}
                                        >
                                            {gymStatusOptions.map((option) => (
                                                <option
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.status} />
                                    </div>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="justify-self-start"
                                    >
                                        {processing
                                            ? 'Menyimpan...'
                                            : 'Perbarui akses'}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>

                    <section className="rounded-xl border p-5">
                        <h2 className="font-semibold">Subscription</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Atur paket, trial, dan periode akses secara manual
                            pada fase foundation.
                        </p>
                        {plans.length === 0 ? (
                            <p className="mt-5 rounded-lg bg-muted/50 p-4 text-sm text-muted-foreground">
                                Aktifkan minimal satu paket SaaS untuk mengatur
                                subscription.
                            </p>
                        ) : (
                            <Form
                                {...PlatformSubscriptionController.update.form(
                                    gym.id,
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
                                                    subscription?.plan_id ??
                                                    plans[0]?.id
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
                                            <Label htmlFor="subscription_status">
                                                Status
                                            </Label>
                                            <select
                                                id="subscription_status"
                                                name="status"
                                                defaultValue={
                                                    subscription?.status ??
                                                    'trialing'
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
                                                    subscription?.trial_ends_at
                                                }
                                                error={errors.trial_ends_at}
                                            />
                                            <DateTimeField
                                                name="current_period_starts_at"
                                                label="Periode dimulai"
                                                value={
                                                    subscription?.current_period_starts_at
                                                }
                                                error={
                                                    errors.current_period_starts_at
                                                }
                                            />
                                            <DateTimeField
                                                name="current_period_ends_at"
                                                label="Periode berakhir"
                                                value={
                                                    subscription?.current_period_ends_at
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
                        )}
                    </section>
                </div>

                <section className="rounded-xl border">
                    <div className="border-b p-5">
                        <h2 className="font-semibold">Pengguna tenant</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Akun dan role yang terhubung ke gym ini.
                        </p>
                    </div>
                    <div className="divide-y">
                        {(gym.users ?? []).map((user) => (
                            <div
                                key={user.id}
                                className="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <Link
                                        href={userShow(user.id)}
                                        className="text-sm font-medium hover:text-primary"
                                    >
                                        {user.name}
                                    </Link>
                                    <p className="text-xs text-muted-foreground">
                                        {user.email}
                                    </p>
                                </div>
                                <span className="text-xs font-medium text-muted-foreground">
                                    {roleLabel(user.role)}
                                </span>
                            </div>
                        ))}
                    </div>
                    <p className="border-t px-5 py-3 text-xs text-muted-foreground">
                        Terdaftar {formatDate(gym.created_at)} · onboarding{' '}
                        {gym.onboarding_completed_at
                            ? 'selesai'
                            : 'belum selesai'}
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
    icon: typeof UsersRound;
    label: string;
    value: number;
}) {
    return (
        <div className="rounded-xl border p-4">
            <Icon className="size-4 text-muted-foreground" />
            <p className="mt-3 text-2xl font-semibold tabular-nums">{value}</p>
            <p className="text-sm text-muted-foreground">{label}</p>
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

function roleLabel(role: string): string {
    return (
        { owner: 'Owner', admin: 'Front Desk', trainer: 'Trainer' }[role] ??
        role
    );
}

PlatformGymShow.layout = {
    breadcrumbs: [
        { title: 'Tenant Gym', href: index() },
        { title: 'Detail gym', href: '#' },
    ],
};
