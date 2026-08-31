import { Form, Head, Link } from '@inertiajs/react';
import { RotateCcw, Search, UserRoundSearch, UsersRound } from 'lucide-react';
import PlatformUserController from '@/actions/App/Http/Controllers/PlatformUserController';
import { PlatformStatusBadge } from '@/components/platform/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/formatters';
import { index, show } from '@/routes/platform/users';
import type { PaginatedPlatformUsers } from '@/types';

type PlanOption = { id: number; name: string };
type Filters = {
    search: string;
    account_type: string;
    status: string;
    plan_id: string;
    billing_status: string;
    per_page: number;
};

const selectClassName =
    'h-9 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export default function PlatformUsersIndex({
    users,
    filters,
    plans,
}: {
    users: PaginatedPlatformUsers;
    filters: Filters;
    plans: PlanOption[];
}) {
    const hasFilters =
        filters.search !== '' ||
        filters.account_type !== '' ||
        filters.status !== '' ||
        filters.plan_id !== '' ||
        filters.billing_status !== '';

    return (
        <>
            <Head title="Pengguna Platform" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <header>
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        Control plane
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold">
                        Pengguna & subscriber
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Pantau akun yang mendaftar, subscription Owner, dan akun
                        staf dari seluruh gym.
                    </p>
                </header>

                <Form
                    {...PlatformUserController.index.form()}
                    options={{ preserveState: true, preserveScroll: true }}
                    className="grid gap-3 border-y py-4 md:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_11rem_10rem_11rem_12rem_auto]"
                >
                    {({ processing }) => (
                        <>
                            <div className="relative">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    name="search"
                                    defaultValue={filters.search}
                                    className="pl-9"
                                    placeholder="Cari nama atau email"
                                />
                            </div>
                            <select
                                name="account_type"
                                defaultValue={filters.account_type}
                                className={selectClassName}
                                aria-label="Jenis akun"
                            >
                                <option value="">Semua jenis akun</option>
                                <option value="subscriber">Subscriber</option>
                                <option value="staff">Staf gym</option>
                                <option value="platform_admin">
                                    Platform Admin
                                </option>
                            </select>
                            <select
                                name="status"
                                defaultValue={filters.status}
                                className={selectClassName}
                                aria-label="Status akses"
                            >
                                <option value="">Semua akses</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                            <select
                                name="plan_id"
                                defaultValue={filters.plan_id}
                                className={selectClassName}
                                aria-label="Paket subscription"
                            >
                                <option value="">Semua paket</option>
                                {plans.map((plan) => (
                                    <option key={plan.id} value={plan.id}>
                                        {plan.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                name="billing_status"
                                defaultValue={filters.billing_status}
                                className={selectClassName}
                                aria-label="Status pembayaran"
                            >
                                <option value="">Semua pembayaran</option>
                                <option value="pending">
                                    Menunggu approval
                                </option>
                            </select>
                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                >
                                    <Search /> Terapkan
                                </Button>
                                {hasFilters && (
                                    <Button variant="ghost" asChild>
                                        <Link href={index()}>
                                            <RotateCcw /> Reset
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </>
                    )}
                </Form>

                {users.data.length === 0 ? (
                    <section className="flex min-h-64 flex-col items-center justify-center rounded-xl border p-8 text-center">
                        <UserRoundSearch className="size-9 text-muted-foreground" />
                        <h2 className="mt-4 font-semibold">
                            Pengguna tidak ditemukan
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Ubah kata pencarian atau filter yang digunakan.
                        </p>
                    </section>
                ) : (
                    <section className="overflow-hidden rounded-xl border">
                        <div className="hidden grid-cols-[minmax(0,1.5fr)_minmax(150px,0.7fr)_minmax(150px,0.7fr)_80px_110px] gap-4 border-b bg-muted/40 px-4 py-3 text-xs font-medium text-muted-foreground md:grid">
                            <span>Pengguna</span>
                            <span>Jenis akun</span>
                            <span>Subscription</span>
                            <span>Gym</span>
                            <span>Status</span>
                        </div>
                        <div className="divide-y">
                            {users.data.map((user) => (
                                <Link
                                    key={user.id}
                                    href={show(user.id)}
                                    className="grid gap-3 p-4 transition-colors hover:bg-muted/30 md:grid-cols-[minmax(0,1.5fr)_minmax(150px,0.7fr)_minmax(150px,0.7fr)_80px_110px] md:items-center md:gap-4"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {user.name}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {user.email} ·{' '}
                                            {formatDate(user.created_at)}
                                        </p>
                                    </div>
                                    <Badge variant="outline">
                                        {user.account_type_label}
                                    </Badge>
                                    <div className="text-sm">
                                        {user.subscription ? (
                                            <>
                                                <p>
                                                    {
                                                        user.subscription
                                                            .plan_name
                                                    }
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {
                                                        user.subscription
                                                            .status_label
                                                    }
                                                </p>
                                                {user.subscription
                                                    .pending_payments_count >
                                                    0 && (
                                                    <p className="mt-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                                                        Menunggu approval
                                                    </p>
                                                )}
                                            </>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                —
                                            </span>
                                        )}
                                    </div>
                                    <span className="flex items-center gap-1.5 text-sm tabular-nums">
                                        <UsersRound className="size-4 text-muted-foreground" />
                                        {user.gyms_count}
                                    </span>
                                    <PlatformStatusBadge
                                        status={
                                            user.is_active
                                                ? 'active'
                                                : 'suspended'
                                        }
                                        label={
                                            user.is_active
                                                ? 'Aktif'
                                                : 'Nonaktif'
                                        }
                                    />
                                </Link>
                            ))}
                        </div>
                    </section>
                )}

                {users.last_page > 1 && (
                    <nav className="flex flex-wrap justify-end gap-2">
                        {users.links.map((link) =>
                            link.url ? (
                                <Button
                                    key={link.label}
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    size="sm"
                                    asChild
                                >
                                    <Link href={link.url} preserveScroll>
                                        {link.label
                                            .replace('&laquo;', '‹')
                                            .replace('&raquo;', '›')}
                                    </Link>
                                </Button>
                            ) : null,
                        )}
                    </nav>
                )}
            </div>
        </>
    );
}

PlatformUsersIndex.layout = {
    breadcrumbs: [{ title: 'Pengguna', href: index() }],
};
