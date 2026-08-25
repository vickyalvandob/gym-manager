import { Head, Link, router } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Eye,
    FilterX,
    Pencil,
    Plus,
    Search,
    UserRoundPlus,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import { MemberAvatar } from '@/components/members/member-avatar';
import { MemberMembershipStatusBadge } from '@/components/members/member-membership-status-badge';
import { MemberStatusBadge } from '@/components/members/member-status-badge';
import { MemberStatusDialog } from '@/components/members/member-status-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/formatters';
import type { PaginatedMembers, SelectOption } from '@/types';

type Filters = {
    search: string;
    status: string;
    per_page: number;
};

export default function MembersIndex({
    members,
    filters,
    statusOptions,
}: {
    members: PaginatedMembers;
    filters: Filters;
    statusOptions: SelectOption[];
}) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [perPage, setPerPage] = useState(String(filters.per_page));
    const hasFilters = filters.search !== '' || filters.status !== '';

    function submitFilters(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get(
            MemberController.index.url(),
            { search, status, per_page: perPage },
            { preserveState: true, replace: true },
        );
    }

    function clearFilters() {
        setSearch('');
        setStatus('');
        setPerPage('15');
        router.get(
            MemberController.index.url(),
            {},
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Member" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {members.total} total
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                            Member
                        </h1>
                    </div>
                    <Button asChild>
                        <Link href={MemberController.create()}>
                            <Plus />
                            Tambah member
                        </Link>
                    </Button>
                </header>

                <form
                    onSubmit={submitFilters}
                    className="grid gap-3 border-y py-4 md:grid-cols-[minmax(14rem,1fr)_11rem_8rem_auto]"
                    aria-label="Filter member"
                >
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari nomor, nama, atau telepon"
                            className="pl-9"
                            aria-label="Cari member"
                        />
                    </div>
                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        aria-label="Filter status"
                    >
                        <option value="">Semua status</option>
                        {statusOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={perPage}
                        onChange={(event) => setPerPage(event.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        aria-label="Jumlah per halaman"
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
                            className="flex-1"
                        >
                            <Search />
                            Terapkan
                        </Button>
                        {hasFilters && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={clearFilters}
                                title="Hapus filter"
                            >
                                <FilterX />
                                <span className="sr-only">Hapus filter</span>
                            </Button>
                        )}
                    </div>
                </form>

                {members.data.length === 0 ? (
                    <div className="flex min-h-64 flex-col items-center justify-center border-y px-6 py-12 text-center">
                        <div className="flex size-10 items-center justify-center rounded-md bg-muted text-muted-foreground">
                            <UserRoundPlus className="size-5" />
                        </div>
                        <p className="mt-4 text-sm font-medium">
                            {hasFilters
                                ? 'Member tidak ditemukan'
                                : 'Belum ada member'}
                        </p>
                        <Button className="mt-4" size="sm" asChild>
                            <Link href={MemberController.create()}>
                                <Plus />
                                Tambah member
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="hidden overflow-hidden rounded-lg border md:block">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/50 text-xs text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Member
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Kontak
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Membership
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {members.data.map((member) => (
                                        <tr
                                            key={member.id}
                                            className="hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-3">
                                                    <MemberAvatar
                                                        name={member.name}
                                                        photoUrl={
                                                            member.photo_url
                                                        }
                                                        className="size-9"
                                                    />
                                                    <div className="min-w-0">
                                                        <Link
                                                            href={MemberController.show(
                                                                member.id,
                                                            )}
                                                            className="block truncate font-medium hover:text-primary"
                                                        >
                                                            {member.name}
                                                        </Link>
                                                        <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                                                            {
                                                                member.member_number
                                                            }
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <p>{member.phone}</p>
                                                <p className="mt-0.5 max-w-56 truncate text-xs text-muted-foreground">
                                                    {member.email ?? '-'}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3">
                                                <MemberStatusBadge
                                                    status={member.status}
                                                    label={member.status_label}
                                                />
                                            </td>
                                            <td className="px-4 py-3">
                                                {member.membership ? (
                                                    <div className="grid justify-items-start gap-1.5">
                                                        <MemberMembershipStatusBadge
                                                            status={
                                                                member
                                                                    .membership
                                                                    .status
                                                            }
                                                            label={
                                                                member
                                                                    .membership
                                                                    .status_label
                                                            }
                                                            isExpiringSoon={
                                                                member
                                                                    .membership
                                                                    .is_expiring_soon
                                                            }
                                                        />
                                                        <p className="text-xs text-muted-foreground">
                                                            {
                                                                member
                                                                    .membership
                                                                    .plan_name
                                                            }{' '}
                                                            · s.d.{' '}
                                                            {formatDate(
                                                                member
                                                                    .membership
                                                                    .end_date,
                                                            )}
                                                        </p>
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">
                                                        Belum ada paket aktif
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Lihat member"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={MemberController.show(
                                                                member.id,
                                                            )}
                                                        >
                                                            <Eye />
                                                            <span className="sr-only">
                                                                Lihat member
                                                            </span>
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Edit member"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={MemberController.edit(
                                                                member.id,
                                                            )}
                                                        >
                                                            <Pencil />
                                                            <span className="sr-only">
                                                                Edit member
                                                            </span>
                                                        </Link>
                                                    </Button>
                                                    <MemberStatusDialog
                                                        memberId={member.id}
                                                        memberName={member.name}
                                                        status={member.status}
                                                        buttonVariant="ghost"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="divide-y border-y md:hidden">
                            {members.data.map((member) => (
                                <article key={member.id} className="py-4">
                                    <div className="flex items-start gap-3">
                                        <MemberAvatar
                                            name={member.name}
                                            photoUrl={member.photo_url}
                                            className="size-10"
                                        />
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="min-w-0">
                                                    <Link
                                                        href={MemberController.show(
                                                            member.id,
                                                        )}
                                                        className="block truncate font-medium"
                                                    >
                                                        {member.name}
                                                    </Link>
                                                    <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                                                        {member.member_number}
                                                    </p>
                                                </div>
                                                <MemberStatusBadge
                                                    status={member.status}
                                                    label={member.status_label}
                                                />
                                            </div>
                                            <p className="mt-3 text-sm">
                                                {member.phone}
                                            </p>
                                            <div className="mt-3 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                {member.membership ? (
                                                    <>
                                                        <MemberMembershipStatusBadge
                                                            status={
                                                                member
                                                                    .membership
                                                                    .status
                                                            }
                                                            label={
                                                                member
                                                                    .membership
                                                                    .status_label
                                                            }
                                                            isExpiringSoon={
                                                                member
                                                                    .membership
                                                                    .is_expiring_soon
                                                            }
                                                        />
                                                        <span>
                                                            {
                                                                member
                                                                    .membership
                                                                    .plan_name
                                                            }{' '}
                                                            · s.d.{' '}
                                                            {formatDate(
                                                                member
                                                                    .membership
                                                                    .end_date,
                                                            )}
                                                        </span>
                                                    </>
                                                ) : (
                                                    <span>
                                                        Belum ada paket aktif
                                                    </span>
                                                )}
                                            </div>
                                            <div className="mt-3 flex gap-1">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={MemberController.show(
                                                            member.id,
                                                        )}
                                                    >
                                                        <Eye />
                                                        Detail
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={MemberController.edit(
                                                            member.id,
                                                        )}
                                                    >
                                                        <Pencil />
                                                        Edit
                                                    </Link>
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            ))}
                        </div>
                    </>
                )}

                {members.last_page > 1 && (
                    <nav
                        className="flex items-center justify-between gap-4 border-t pt-4"
                        aria-label="Pagination member"
                    >
                        <p className="hidden text-sm text-muted-foreground sm:block">
                            {members.from}-{members.to} dari {members.total}
                        </p>
                        <div className="flex w-full items-center justify-between gap-1 sm:w-auto sm:justify-end">
                            <PaginationButton
                                url={members.links[0]?.url ?? null}
                                label="Sebelumnya"
                                icon={<ChevronLeft />}
                            />
                            <div className="hidden gap-1 sm:flex">
                                {members.links.slice(1, -1).map((link) => (
                                    <Button
                                        key={link.label}
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        size="icon"
                                        disabled={link.url === null}
                                        asChild={link.url !== null}
                                    >
                                        {link.url ? (
                                            <Link
                                                href={link.url}
                                                preserveScroll
                                            >
                                                {link.label}
                                            </Link>
                                        ) : (
                                            <span>{link.label}</span>
                                        )}
                                    </Button>
                                ))}
                            </div>
                            <PaginationButton
                                url={members.links.at(-1)?.url ?? null}
                                label="Berikutnya"
                                icon={<ChevronRight />}
                                iconAfter
                            />
                        </div>
                    </nav>
                )}
            </div>
        </>
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

MembersIndex.layout = {
    breadcrumbs: [{ title: 'Member', href: MemberController.index() }],
};
