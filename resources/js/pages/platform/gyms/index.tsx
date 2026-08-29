import { Form, Head, Link } from '@inertiajs/react';
import { Building2, Search } from 'lucide-react';
import PlatformGymController from '@/actions/App/Http/Controllers/PlatformGymController';
import { PlatformStatusBadge } from '@/components/platform/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/formatters';
import { index, show } from '@/routes/platform/gyms';
import type { PaginatedPlatformGyms, SelectOption } from '@/types';

type Props = {
    gyms: PaginatedPlatformGyms;
    filters: {
        search: string;
        status: string;
        subscription_status: string;
        per_page: number;
    };
    gymStatusOptions: SelectOption[];
    subscriptionStatusOptions: SelectOption[];
};

const selectClassName =
    'h-10 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function PlatformGymIndex({
    gyms,
    filters,
    gymStatusOptions,
    subscriptionStatusOptions,
}: Props) {
    return (
        <>
            <Head title="Tenant Gym" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Tenant gym</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Kelola onboarding, subscription, dan akses seluruh gym.
                    </p>
                </div>

                <Form
                    {...PlatformGymController.index.form()}
                    className="grid gap-3 rounded-xl border p-4 md:grid-cols-[minmax(220px,1fr)_180px_220px_auto]"
                >
                    <div className="relative">
                        <Search className="absolute top-3 left-3 size-4 text-muted-foreground" />
                        <Input
                            name="search"
                            defaultValue={filters.search}
                            className="pl-9"
                            placeholder="Cari nama atau slug"
                        />
                    </div>
                    <select
                        name="status"
                        defaultValue={filters.status}
                        className={selectClassName}
                    >
                        <option value="">Semua status gym</option>
                        {gymStatusOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <select
                        name="subscription_status"
                        defaultValue={filters.subscription_status}
                        className={selectClassName}
                    >
                        <option value="">Semua subscription</option>
                        {subscriptionStatusOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <Button type="submit">Terapkan</Button>
                </Form>

                {gyms.data.length === 0 ? (
                    <div className="flex min-h-64 flex-col items-center justify-center rounded-xl border p-8 text-center">
                        <Building2 className="size-9 text-muted-foreground" />
                        <p className="mt-3 font-medium">Gym tidak ditemukan</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Ubah filter atau tunggu onboarding tenant baru.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border">
                        <div className="hidden grid-cols-[minmax(220px,1fr)_130px_170px_90px_120px] gap-4 border-b bg-muted/30 px-4 py-3 text-xs font-medium text-muted-foreground md:grid">
                            <span>Gym</span>
                            <span>Status</span>
                            <span>Subscription</span>
                            <span>Pengguna</span>
                            <span>Terdaftar</span>
                        </div>
                        <div className="divide-y">
                            {gyms.data.map((gym) => (
                                <Link
                                    key={gym.id}
                                    href={show(gym.id)}
                                    className="grid gap-3 p-4 transition-colors hover:bg-muted/30 md:grid-cols-[minmax(220px,1fr)_130px_170px_90px_120px] md:items-center md:gap-4"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {gym.name}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {gym.slug}
                                        </p>
                                    </div>
                                    <PlatformStatusBadge
                                        status={gym.status}
                                        label={gym.status_label}
                                    />
                                    <div className="min-w-0">
                                        {gym.subscription ? (
                                            <>
                                                <PlatformStatusBadge
                                                    status={
                                                        gym.subscription.status
                                                    }
                                                    label={
                                                        gym.subscription
                                                            .status_label
                                                    }
                                                />
                                                <p className="mt-1 truncate text-xs text-muted-foreground">
                                                    {gym.subscription.plan_name}
                                                </p>
                                            </>
                                        ) : (
                                            <span className="text-xs text-muted-foreground">
                                                Belum diatur
                                            </span>
                                        )}
                                    </div>
                                    <span className="text-sm tabular-nums">
                                        {gym.users_count}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {formatDate(gym.created_at)}
                                    </span>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

                {gyms.last_page > 1 && (
                    <nav className="flex flex-wrap justify-end gap-2">
                        {gyms.links.map((link) =>
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

PlatformGymIndex.layout = {
    breadcrumbs: [{ title: 'Tenant Gym', href: index() }],
};
