import { Form, Head, Link } from '@inertiajs/react';
import {
    Dumbbell,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    UsersRound,
} from 'lucide-react';
import StaffController from '@/actions/App/Http/Controllers/StaffController';
import TrainerController from '@/actions/App/Http/Controllers/TrainerController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/formatters';
import { index } from '@/routes/staff';
import { edit as trainerEdit } from '@/routes/trainers';
import type { PaginatedStaff, StaffMember } from '@/types';

type Filters = {
    search: string;
    role: string;
    status: string;
    per_page: number;
};

type Quota = {
    used: number;
    limit: number | null;
    plan_name: string;
} | null;

const selectClassName =
    'h-9 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export default function StaffIndex({
    staff,
    filters,
    quota,
}: {
    staff: PaginatedStaff;
    filters: Filters;
    quota: Quota;
}) {
    const hasFilters =
        filters.search !== '' || filters.role !== '' || filters.status !== '';

    return (
        <>
            <Head title="Staf Gym" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            Tim gym aktif
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold">
                            Staf Gym
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Kelola Front Desk dan lihat akun Trainer khusus gym
                            yang sedang dipilih.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={TrainerController.create()}>
                                <Dumbbell /> Tambah Trainer
                            </Link>
                        </Button>
                        <Button size="sm" asChild>
                            <Link href={StaffController.create()}>
                                <Plus /> Tambah Front Desk
                            </Link>
                        </Button>
                    </div>
                </header>

                {quota && (
                    <section className="flex flex-col gap-3 rounded-xl border bg-muted/20 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-medium">
                                Kuota staf paket {quota.plan_name}
                            </p>
                            <p className="mt-0.5 text-xs text-muted-foreground">
                                Dihitung gabungan dari seluruh gym dalam satu
                                subscription, tidak termasuk Owner.
                            </p>
                        </div>
                        <p className="text-xl font-semibold tabular-nums">
                            {quota.used} / {quota.limit ?? '∞'}
                        </p>
                    </section>
                )}

                <Form
                    {...StaffController.index.form()}
                    options={{ preserveState: true, preserveScroll: true }}
                    className="grid gap-3 border-y py-4 sm:grid-cols-[minmax(0,1fr)_10rem_10rem_auto]"
                >
                    {({ processing }) => (
                        <>
                            <div className="relative">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    name="search"
                                    defaultValue={filters.search}
                                    className="pl-9"
                                    placeholder="Cari nama atau email staf"
                                />
                            </div>
                            <select
                                name="role"
                                defaultValue={filters.role}
                                className={selectClassName}
                                aria-label="Filter role staf"
                            >
                                <option value="">Semua role</option>
                                <option value="admin">Front Desk</option>
                                <option value="trainer">Trainer</option>
                            </select>
                            <select
                                name="status"
                                defaultValue={filters.status}
                                className={selectClassName}
                                aria-label="Filter status staf"
                            >
                                <option value="">Semua status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                >
                                    <Search /> Cari
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

                {staff.data.length === 0 ? (
                    <section className="flex min-h-64 flex-col items-center justify-center border-y p-8 text-center">
                        <UsersRound className="size-9 text-muted-foreground" />
                        <h2 className="mt-4 font-semibold">
                            {hasFilters
                                ? 'Staf tidak ditemukan'
                                : 'Belum ada staf gym'}
                        </h2>
                        <p className="mt-1 max-w-md text-sm text-muted-foreground">
                            {hasFilters
                                ? 'Ubah pencarian atau filter untuk melihat hasil lain.'
                                : 'Tambahkan Front Desk untuk operasional harian, atau Trainer untuk layanan PT.'}
                        </p>
                    </section>
                ) : (
                    <section className="overflow-hidden rounded-xl border">
                        <div className="hidden grid-cols-[minmax(0,1.4fr)_160px_140px_130px_70px] gap-4 border-b bg-muted/40 px-4 py-3 text-xs font-medium text-muted-foreground md:grid">
                            <span>Staf</span>
                            <span>Role</span>
                            <span>Status gym</span>
                            <span>Ditambahkan</span>
                            <span className="text-right">Aksi</span>
                        </div>
                        <div className="divide-y">
                            {staff.data.map((member) => (
                                <StaffRow key={member.id} member={member} />
                            ))}
                        </div>
                    </section>
                )}

                {staff.last_page > 1 && (
                    <nav className="flex flex-wrap justify-end gap-2">
                        {staff.links.map((link) =>
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

function StaffRow({ member }: { member: StaffMember }) {
    const editHref =
        member.role === 'trainer' && member.trainer_id
            ? trainerEdit(member.trainer_id)
            : StaffController.edit(member.id);

    return (
        <div className="grid gap-3 p-4 md:grid-cols-[minmax(0,1.4fr)_160px_140px_130px_70px] md:items-center md:gap-4">
            <div className="min-w-0">
                <p className="truncate text-sm font-medium">{member.name}</p>
                <p className="truncate text-xs text-muted-foreground">
                    {member.email}
                    {member.trainer_code ? ` · ${member.trainer_code}` : ''}
                </p>
                {!member.is_account_active && (
                    <p className="mt-1 text-xs text-destructive">
                        Akun dinonaktifkan oleh Platform Admin
                    </p>
                )}
            </div>
            <Badge variant="outline">{member.role_label}</Badge>
            <Badge
                variant={member.status === 'active' ? 'secondary' : 'outline'}
            >
                {member.status_label}
            </Badge>
            <span className="text-xs text-muted-foreground">
                {formatDate(member.created_at)}
            </span>
            <div className="text-right">
                <Button variant="ghost" size="icon" asChild>
                    <Link href={editHref} aria-label={`Edit ${member.name}`}>
                        <Pencil />
                    </Link>
                </Button>
            </div>
        </div>
    );
}

StaffIndex.layout = {
    breadcrumbs: [{ title: 'Staf Gym', href: index() }],
};
