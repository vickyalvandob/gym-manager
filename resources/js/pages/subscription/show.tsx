import { Head } from '@inertiajs/react';
import { CalendarClock, CircleAlert, WalletCards } from 'lucide-react';
import { PlatformStatusBadge } from '@/components/platform/status-badge';
import { formatCurrency, formatDate } from '@/lib/formatters';
import { show } from '@/routes/subscription';

type Props = {
    gym: {
        id: number;
        name: string;
        status: string;
        status_label: string;
    };
    subscription: {
        id: number;
        status: string;
        status_label: string;
        plan_name: string;
        price: string;
        currency: string;
        billing_interval_label: string;
        trial_ends_at: string | null;
        current_period_starts_at: string | null;
        current_period_ends_at: string | null;
        grants_access: boolean;
    } | null;
};

export default function SubscriptionShow({ gym, subscription }: Props) {
    return (
        <>
            <Head title="Subscription" />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-6">
                <div>
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        Akun gym
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold">
                        Subscription GymFlow
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Status akses platform untuk {gym.name}.
                    </p>
                </div>

                {subscription === null ? (
                    <div className="rounded-xl border border-amber-300 bg-amber-50 p-5 text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                        <div className="flex items-start gap-3">
                            <CircleAlert className="mt-0.5 size-5" />
                            <div>
                                <h2 className="font-semibold">
                                    Subscription belum diatur
                                </h2>
                                <p className="mt-1 text-sm">
                                    Hubungi Platform Admin untuk menetapkan
                                    paket SaaS.
                                </p>
                            </div>
                        </div>
                    </div>
                ) : (
                    <section className="rounded-xl border">
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
                                    per{' '}
                                    {subscription.billing_interval_label.toLowerCase()}
                                </p>
                            </div>
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
                        </div>
                        <dl className="grid gap-5 p-5 sm:grid-cols-3">
                            <Info
                                label="Trial berakhir"
                                value={formatDate(subscription.trial_ends_at)}
                            />
                            <Info
                                label="Periode dimulai"
                                value={formatDate(
                                    subscription.current_period_starts_at,
                                )}
                            />
                            <Info
                                label="Periode berakhir"
                                value={formatDate(
                                    subscription.current_period_ends_at,
                                )}
                            />
                        </dl>
                    </section>
                )}

                <div className="flex items-start gap-3 rounded-xl border p-5">
                    <CalendarClock className="mt-0.5 size-5 text-muted-foreground" />
                    <div>
                        <h2 className="font-medium">
                            Pembayaran otomatis belum diaktifkan
                        </h2>
                        <p className="mt-1 text-sm leading-6 text-muted-foreground">
                            Phase foundation mengelola trial dan status secara
                            manual oleh Platform Admin. Checkout dan webhook
                            payment gateway akan menjadi fase billing
                            berikutnya.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}

function Info({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="mt-1 font-medium">{value}</dd>
        </div>
    );
}

SubscriptionShow.layout = {
    breadcrumbs: [{ title: 'Subscription', href: show() }],
};
