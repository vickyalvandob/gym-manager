import { Form, Head, Link } from '@inertiajs/react';
import { Building2, CheckCircle2, Plus, UsersRound } from 'lucide-react';
import InputError from '@/components/input-error';
import { PlatformStatusBadge } from '@/components/platform/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store, switchMethod as switchGym } from '@/routes/gyms';

type GymItem = {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    onboarding_completed: boolean;
    is_current: boolean;
};

type Props = {
    gyms: GymItem[];
    subscription: {
        plan_name: string;
        grants_access: boolean;
        usage: { gyms: number; members: number; staff: number };
        limits: {
            gyms: number | null;
            members: number | null;
            staff: number | null;
        };
        can_create_gym: boolean;
    };
};

export default function GymIndex({ gyms, subscription }: Props) {
    return (
        <>
            <Head title="Gym Saya" />
            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-6">
                <div>
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        Workspace
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold">Gym Saya</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Semua gym ini memakai satu subscription{' '}
                        {subscription.plan_name}.
                    </p>
                </div>

                <section className="grid gap-3 sm:grid-cols-3">
                    <UsageCard
                        icon={Building2}
                        label="Gym"
                        usage={subscription.usage.gyms}
                        limit={subscription.limits.gyms}
                    />
                    <UsageCard
                        icon={UsersRound}
                        label="Member"
                        usage={subscription.usage.members}
                        limit={subscription.limits.members}
                    />
                    <UsageCard
                        icon={UsersRound}
                        label="Staf"
                        usage={subscription.usage.staff}
                        limit={subscription.limits.staff}
                    />
                </section>

                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                    <section className="rounded-xl border">
                        <div className="border-b p-5">
                            <h2 className="font-semibold">Daftar gym</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Pilih gym untuk berpindah workspace tanpa login
                                ulang.
                            </p>
                        </div>
                        <div className="divide-y">
                            {gyms.map((gym) => (
                                <div
                                    key={gym.id}
                                    className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h3 className="truncate font-medium">
                                                {gym.name}
                                            </h3>
                                            <PlatformStatusBadge
                                                status={gym.status}
                                                label={gym.status_label}
                                            />
                                            {gym.is_current && (
                                                <span className="inline-flex items-center gap-1 text-xs font-medium text-primary">
                                                    <CheckCircle2 className="size-3.5" />
                                                    Aktif
                                                </span>
                                            )}
                                        </div>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {gym.onboarding_completed
                                                ? 'Siap digunakan'
                                                : 'Onboarding belum selesai'}
                                        </p>
                                    </div>
                                    {!gym.is_current && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={switchGym(gym.id)}
                                                method="put"
                                                as="button"
                                            >
                                                Buka gym
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="rounded-xl border p-5">
                        <div className="flex items-center gap-2">
                            <Plus className="size-5 text-muted-foreground" />
                            <h2 className="font-semibold">Tambah gym</h2>
                        </div>
                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                            Gym baru memakai paket dan kuota subscription yang
                            sama.
                        </p>
                        {subscription.can_create_gym ? (
                            <Form {...store.form()} className="mt-5 grid gap-4">
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">
                                                Nama gym
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                required
                                                maxLength={120}
                                                placeholder="Contoh: Gymlo Bandung"
                                            />
                                            <InputError message={errors.name} />
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            <Plus />
                                            {processing
                                                ? 'Membuat...'
                                                : 'Buat gym baru'}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        ) : (
                            <p className="mt-5 rounded-lg bg-muted/50 p-4 text-sm text-muted-foreground">
                                {subscription.grants_access
                                    ? 'Batas gym paket sudah tercapai. Upgrade paket untuk menambah gym.'
                                    : 'Aktifkan subscription sebelum menambah gym.'}
                            </p>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

function UsageCard({
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
        <div className="rounded-xl border p-4">
            <Icon className="size-4 text-muted-foreground" />
            <p className="mt-3 text-2xl font-semibold tabular-nums">
                {usage}
                <span className="text-sm font-normal text-muted-foreground">
                    {' '}
                    / {limit ?? 'tanpa batas'}
                </span>
            </p>
            <p className="text-sm text-muted-foreground">{label} terpakai</p>
        </div>
    );
}

GymIndex.layout = {
    breadcrumbs: [{ title: 'Gym Saya', href: index() }],
};
