import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, WalletCards } from 'lucide-react';
import SaasPlanController from '@/actions/App/Http/Controllers/SaasPlanController';
import { PlatformStatusBadge } from '@/components/platform/status-badge';
import { Button } from '@/components/ui/button';
import { formatCurrency } from '@/lib/formatters';
import { index } from '@/routes/platform/saas-plans';
import type { SaasPlan } from '@/types';

export default function SaasPlanIndex({ plans }: { plans: SaasPlan[] }) {
    return (
        <>
            <Head title="Paket SaaS" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Platform
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold">
                            Paket SaaS
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Atur harga, trial, dan kapasitas untuk onboarding
                            gym.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={SaasPlanController.create()}>
                            <Plus /> Tambah paket
                        </Link>
                    </Button>
                </div>

                {plans.length === 0 ? (
                    <div className="flex min-h-64 flex-col items-center justify-center rounded-xl border p-8 text-center">
                        <WalletCards className="size-9 text-muted-foreground" />
                        <p className="mt-3 font-medium">Belum ada paket SaaS</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Buat minimal satu paket aktif sebelum membuka
                            pendaftaran.
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                        {plans.map((plan) => (
                            <article
                                key={plan.id}
                                className="rounded-xl border p-5"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h2 className="font-semibold">
                                            {plan.name}
                                        </h2>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {plan.subscriptions_count}{' '}
                                            subscription
                                        </p>
                                    </div>
                                    <PlatformStatusBadge
                                        status={
                                            plan.is_active
                                                ? 'active'
                                                : 'cancelled'
                                        }
                                        label={
                                            plan.is_active
                                                ? 'Aktif'
                                                : 'Nonaktif'
                                        }
                                    />
                                </div>
                                <p className="mt-5 text-2xl font-semibold tabular-nums">
                                    {formatCurrency(plan.price, plan.currency)}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    per{' '}
                                    {plan.billing_interval_label.toLowerCase()}{' '}
                                    · trial {plan.trial_days} hari
                                </p>
                                <dl className="mt-5 grid grid-cols-2 gap-3 border-y py-4 text-sm">
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Member
                                        </dt>
                                        <dd className="mt-1 font-medium">
                                            {plan.max_members ?? 'Tanpa batas'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">
                                            Staf
                                        </dt>
                                        <dd className="mt-1 font-medium">
                                            {plan.max_staff ?? 'Tanpa batas'}
                                        </dd>
                                    </div>
                                </dl>
                                <div className="mt-4 flex items-center justify-between gap-2">
                                    <Form
                                        {...SaasPlanController.updateStatus.form(
                                            plan.id,
                                        )}
                                    >
                                        {({ processing }) => (
                                            <>
                                                <input
                                                    type="hidden"
                                                    name="is_active"
                                                    value={
                                                        plan.is_active
                                                            ? '0'
                                                            : '1'
                                                    }
                                                />
                                                <Button
                                                    type="submit"
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={processing}
                                                >
                                                    {plan.is_active
                                                        ? 'Nonaktifkan'
                                                        : 'Aktifkan'}
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link
                                            href={SaasPlanController.edit(
                                                plan.id,
                                            )}
                                        >
                                            <Pencil /> Edit
                                        </Link>
                                    </Button>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

SaasPlanIndex.layout = {
    breadcrumbs: [{ title: 'Paket SaaS', href: index() }],
};
