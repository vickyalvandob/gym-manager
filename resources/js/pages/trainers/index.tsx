import { Form, Head, Link } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Dumbbell,
    Mail,
    Pencil,
    Phone,
    Plus,
    RotateCcw,
    Search,
    UsersRound,
} from 'lucide-react';
import type { ReactNode } from 'react';
import TrainerController from '@/actions/App/Http/Controllers/TrainerController';
import { TrainerStatusBadge } from '@/components/trainers/trainer-status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, show } from '@/routes/trainers';
import type { PaginatedTrainers, Trainer } from '@/types';

type TrainerFilters = {
    search: string;
    status: string;
    per_page: number;
};

export default function TrainersIndex({
    trainers,
    filters,
    canCreate,
}: {
    trainers: PaginatedTrainers;
    filters: TrainerFilters;
    canCreate: boolean;
}) {
    const hasFilters = filters.search !== '' || filters.status !== '';

    return (
        <>
            <Head title="Trainer" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            Tim trainer
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                            Trainer
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Kelola profil trainer dan member yang ditangani.
                        </p>
                    </div>
                    {canCreate && (
                        <Button size="sm" asChild>
                            <Link href={TrainerController.create()}>
                                <Plus />
                                Tambah trainer
                            </Link>
                        </Button>
                    )}
                </header>

                <Form
                    {...TrainerController.index.form()}
                    options={{ preserveState: true, preserveScroll: true }}
                    className="grid gap-3 border-y py-4 sm:grid-cols-[minmax(0,1fr)_11rem_7rem_auto]"
                >
                    {({ processing }) => (
                        <>
                            <div className="relative">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    name="search"
                                    defaultValue={filters.search}
                                    className="pl-9"
                                    placeholder="Cari nama, telepon, email, atau spesialisasi"
                                    aria-label="Cari trainer"
                                />
                            </div>
                            <select
                                name="status"
                                defaultValue={filters.status}
                                aria-label="Filter status trainer"
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            >
                                <option value="">Semua status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                            <select
                                name="per_page"
                                defaultValue={String(filters.per_page)}
                                aria-label="Jumlah trainer per halaman"
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            >
                                {[10, 15, 25, 50].map((value) => (
                                    <option key={value} value={value}>
                                        {value} / halaman
                                    </option>
                                ))}
                            </select>
                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                >
                                    <Search />
                                    Cari
                                </Button>
                                {hasFilters && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        asChild
                                    >
                                        <Link href={index()}>
                                            <RotateCcw />
                                            Reset
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </>
                    )}
                </Form>

                {trainers.data.length === 0 ? (
                    <section className="flex min-h-72 flex-col items-center justify-center border-y px-6 py-12 text-center">
                        <Dumbbell className="size-9 text-muted-foreground" />
                        <h2 className="mt-4 text-base font-semibold">
                            {hasFilters
                                ? 'Trainer tidak ditemukan'
                                : 'Belum ada trainer'}
                        </h2>
                        <p className="mt-1 max-w-md text-sm leading-6 text-muted-foreground">
                            {hasFilters
                                ? 'Ubah kata pencarian atau filter status untuk melihat hasil lain.'
                                : 'Tambahkan trainer pertama agar assignment member dapat mulai dikelola.'}
                        </p>
                        {canCreate && !hasFilters && (
                            <Button className="mt-5" size="sm" asChild>
                                <Link href={TrainerController.create()}>
                                    <Plus />
                                    Tambah trainer
                                </Link>
                            </Button>
                        )}
                        {hasFilters && (
                            <Button
                                className="mt-5"
                                size="sm"
                                variant="outline"
                                asChild
                            >
                                <Link href={index()}>
                                    <RotateCcw />
                                    Hapus filter
                                </Link>
                            </Button>
                        )}
                    </section>
                ) : (
                    <>
                        <div className="hidden overflow-hidden rounded-lg border md:block">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-left text-xs text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Trainer
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Kontak
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Assignment
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                        <th className="w-24 px-4 py-3 text-right font-medium">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {trainers.data.map((trainer) => (
                                        <TrainerRow
                                            key={trainer.id}
                                            trainer={trainer}
                                            canEdit={canCreate}
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="grid gap-3 md:hidden">
                            {trainers.data.map((trainer) => (
                                <TrainerCard
                                    key={trainer.id}
                                    trainer={trainer}
                                />
                            ))}
                        </div>

                        <Pagination
                            links={trainers.links}
                            from={trainers.from}
                            to={trainers.to}
                            total={trainers.total}
                        />
                    </>
                )}
            </div>
        </>
    );
}

function TrainerRow({
    trainer,
    canEdit,
}: {
    trainer: Trainer;
    canEdit: boolean;
}) {
    return (
        <tr className="hover:bg-muted/30">
            <td className="px-4 py-3">
                <Link
                    href={show(trainer.id)}
                    className="font-medium hover:text-primary"
                >
                    {trainer.name}
                </Link>
                <p className="mt-0.5 text-xs text-muted-foreground">
                    {trainer.trainer_code ?? 'Tanpa kode'} ·{' '}
                    {trainer.specialization ?? 'Spesialisasi belum diisi'}
                </p>
            </td>
            <td className="px-4 py-3 text-muted-foreground">
                <p>{trainer.phone}</p>
                <p className="mt-0.5 text-xs">{trainer.email ?? '—'}</p>
            </td>
            <td className="px-4 py-3">
                <span className="inline-flex items-center gap-1.5 tabular-nums">
                    <UsersRound className="size-4 text-muted-foreground" />
                    {trainer.members_count} member
                </span>
            </td>
            <td className="px-4 py-3">
                <TrainerStatusBadge
                    status={trainer.status}
                    label={trainer.status_label}
                />
            </td>
            <td className="px-4 py-3 text-right">
                {canEdit ? (
                    <Button variant="ghost" size="icon" asChild>
                        <Link
                            href={TrainerController.edit(trainer.id)}
                            aria-label={`Edit ${trainer.name}`}
                        >
                            <Pencil />
                        </Link>
                    </Button>
                ) : (
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={show(trainer.id)}>Detail</Link>
                    </Button>
                )}
            </td>
        </tr>
    );
}

function TrainerCard({ trainer }: { trainer: Trainer }) {
    return (
        <article className="rounded-lg border p-4">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <Link
                        href={show(trainer.id)}
                        className="font-medium hover:text-primary"
                    >
                        {trainer.name}
                    </Link>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {trainer.trainer_code ?? 'Tanpa kode'} ·{' '}
                        {trainer.specialization ?? 'Spesialisasi belum diisi'}
                    </p>
                </div>
                <TrainerStatusBadge
                    status={trainer.status}
                    label={trainer.status_label}
                />
            </div>
            <div className="mt-4 grid gap-2 text-sm text-muted-foreground">
                <p className="flex items-center gap-2">
                    <Phone className="size-4" />
                    {trainer.phone}
                </p>
                {trainer.email && (
                    <p className="flex items-center gap-2">
                        <Mail className="size-4" />
                        {trainer.email}
                    </p>
                )}
                <p className="flex items-center gap-2">
                    <UsersRound className="size-4" />
                    {trainer.members_count} member ditangani
                </p>
            </div>
            <Button variant="outline" size="sm" className="mt-4 w-full" asChild>
                <Link href={show(trainer.id)}>Lihat detail</Link>
            </Button>
        </article>
    );
}

function Pagination({
    links,
    from,
    to,
    total,
}: {
    links: PaginatedTrainers['links'];
    from: number | null;
    to: number | null;
    total: number;
}) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav className="flex flex-col items-center justify-between gap-3 border-t pt-4 text-sm sm:flex-row">
            <p className="text-muted-foreground">
                Menampilkan {from ?? 0}–{to ?? 0} dari {total} trainer
            </p>
            <div className="flex items-center gap-2">
                <PaginationButton
                    url={links.at(0)?.url ?? null}
                    label="Sebelumnya"
                    icon={<ChevronLeft />}
                />
                <PaginationButton
                    url={links.at(-1)?.url ?? null}
                    label="Berikutnya"
                    icon={<ChevronRight />}
                    iconAfter
                />
            </div>
        </nav>
    );
}

function PaginationButton({
    url,
    label,
    icon,
    iconAfter = false,
}: {
    url: string | null;
    label: string;
    icon: ReactNode;
    iconAfter?: boolean;
}) {
    return (
        <Button
            variant="outline"
            size="sm"
            disabled={!url}
            asChild={Boolean(url)}
        >
            {url ? (
                <Link href={url} preserveScroll>
                    {!iconAfter && icon}
                    {label}
                    {iconAfter && icon}
                </Link>
            ) : (
                <span>
                    {!iconAfter && icon}
                    {label}
                    {iconAfter && icon}
                </span>
            )}
        </Button>
    );
}

TrainersIndex.layout = {
    breadcrumbs: [{ title: 'Trainer', href: index() }],
};
