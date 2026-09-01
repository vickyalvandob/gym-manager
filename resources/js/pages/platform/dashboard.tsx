import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    Building2,
    CircleAlert,
    UsersRound,
    WalletCards,
} from 'lucide-react';
import { PlatformStatusBadge } from '@/components/platform/status-badge';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/formatters';
import { dashboard } from '@/routes/platform';
import { index as plansIndex } from '@/routes/platform/saas-plans';
import { index as usersIndex, show as userShow } from '@/routes/platform/users';

type Props = {
    metrics: Record<string, number>;
    recentGyms: Array<{
        id: number;
        name: string;
        slug: string;
        status: string;
        status_label: string;
        subscription_status: string | null;
        subscription_status_label: string | null;
        plan_name: string | null;
        subscriber_id: number | null;
        subscriber_name: string | null;
        created_at: string;
    }>;
    recentActivity: Array<{
        id: number;
        event: string;
        actor_name: string;
        created_at: string;
    }>;
};

export default function PlatformDashboard({
    metrics,
    recentGyms,
    recentActivity,
}: Props) {
    return (
        <>
            <Head title="Platform Admin" />
            <div className="flex flex-col gap-7 p-4 md:p-6">
                <div>
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        Control plane
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold">
                        Ringkasan Gymlo
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Pantau tenant, trial, dan subscription tanpa masuk ke
                        data operasional gym.
                    </p>
                </div>

                <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
                    <Metric
                        label="Pengguna"
                        value={metrics.users_total}
                        icon={UsersRound}
                    />
                    <Metric
                        label="Total gym"
                        value={metrics.gyms_total}
                        icon={Building2}
                    />
                    <Metric
                        label="Subscriber"
                        value={metrics.subscribers_total}
                        icon={WalletCards}
                    />
                    <Metric
                        label="Subscription aktif"
                        value={metrics.subscriptions_active}
                        icon={WalletCards}
                    />
                    <Metric
                        label="Sedang trial"
                        value={metrics.subscriptions_trialing}
                        icon={Activity}
                    />
                    <Metric
                        label="Perlu perhatian"
                        value={metrics.subscriptions_attention}
                        icon={CircleAlert}
                    />
                    <Metric
                        label="Menunggu approval"
                        value={metrics.subscription_payments_pending}
                        icon={CircleAlert}
                    />
                </section>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.6fr)]">
                    <section className="rounded-xl border">
                        <div className="flex items-center justify-between gap-3 border-b p-4">
                            <div>
                                <h2 className="font-semibold">
                                    Subscriber & gym terbaru
                                </h2>
                                <p className="text-xs text-muted-foreground">
                                    Akun subscriber dan gym yang baru terdaftar.
                                </p>
                            </div>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={usersIndex()}>
                                    Lihat subscriber
                                </Link>
                            </Button>
                        </div>
                        <div className="divide-y">
                            {recentGyms.length === 0 ? (
                                <p className="p-6 text-sm text-muted-foreground">
                                    Belum ada tenant gym.
                                </p>
                            ) : (
                                recentGyms.map((gym) => (
                                    <Link
                                        key={gym.id}
                                        href={
                                            gym.subscriber_id
                                                ? userShow(gym.subscriber_id)
                                                : usersIndex()
                                        }
                                        className="flex items-center justify-between gap-4 p-4 transition-colors hover:bg-muted/40"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {gym.subscriber_name ??
                                                    gym.name}
                                            </p>
                                            <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                                {gym.plan_name ??
                                                    'Belum ada paket'}{' '}
                                                · {gym.slug}
                                            </p>
                                        </div>
                                        <PlatformStatusBadge
                                            status={
                                                gym.subscription_status ??
                                                gym.status
                                            }
                                            label={
                                                gym.subscription_status_label ??
                                                gym.status_label
                                            }
                                        />
                                    </Link>
                                ))
                            )}
                        </div>
                    </section>

                    <section className="rounded-xl border">
                        <div className="border-b p-4">
                            <h2 className="font-semibold">
                                Aktivitas platform
                            </h2>
                            <p className="text-xs text-muted-foreground">
                                Perubahan control plane terbaru.
                            </p>
                        </div>
                        <div className="divide-y">
                            {recentActivity.length === 0 ? (
                                <p className="p-6 text-sm text-muted-foreground">
                                    Belum ada aktivitas.
                                </p>
                            ) : (
                                recentActivity.map((item) => (
                                    <div key={item.id} className="p-4">
                                        <p className="text-sm font-medium">
                                            {eventLabel(item.event)}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {item.actor_name} ·{' '}
                                            {formatDateTime(item.created_at)}
                                        </p>
                                    </div>
                                ))
                            )}
                        </div>
                    </section>
                </div>

                <div className="flex flex-wrap justify-end gap-2">
                    <Button variant="outline" asChild>
                        <Link href={usersIndex()}>Kelola pengguna</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={plansIndex()}>Kelola paket SaaS</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}

function Metric({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value: number;
    icon: typeof Building2;
}) {
    return (
        <div className="rounded-xl border p-4">
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm text-muted-foreground">{label}</p>
                <Icon className="size-4 text-muted-foreground" />
            </div>
            <p className="mt-3 text-2xl font-semibold tabular-nums">{value}</p>
        </div>
    );
}

function eventLabel(event: string): string {
    return (
        {
            'gym.registered': 'Gym baru mendaftar',
            'gym.status_changed': 'Status gym diperbarui',
            'subscription.updated': 'Subscription diperbarui',
            'user.access_updated': 'Akses pengguna diperbarui',
            'saas_plan.created': 'Paket SaaS dibuat',
            'saas_plan.updated': 'Paket SaaS diperbarui',
            'saas_plan.status_changed': 'Status paket diperbarui',
        }[event] ?? event
    );
}

PlatformDashboard.layout = {
    breadcrumbs: [{ title: 'Platform', href: dashboard() }],
};
