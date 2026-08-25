import { Head, Link, router, usePage, usePoll } from '@inertiajs/react';
import {
    CalendarClock,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Clock3,
    FilterX,
    History,
    Search,
    UserRoundSearch,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import CheckInController from '@/actions/App/Http/Controllers/CheckInController';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import { CheckInButton } from '@/components/check-ins/check-in-button';
import { MemberAvatar } from '@/components/members/member-avatar';
import { MemberStatusBadge } from '@/components/members/member-status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate, formatDateTime } from '@/lib/formatters';
import type {
    CheckInIndexProps,
    CheckInListItem,
    CheckInMemberSearchResult,
} from '@/types';

export default function CheckInsIndex({
    memberSearchResults,
    recentCheckIns,
    history,
    todayCount,
    filters,
    duplicateWindowMinutes,
}: CheckInIndexProps) {
    const { auth } = usePage().props;
    const timezone = auth.currentGym?.timezone ?? 'Asia/Jakarta';
    const [memberSearch, setMemberSearch] = useState(filters.member_search);
    const [historySearch, setHistorySearch] = useState(filters.history_search);
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [perPage, setPerPage] = useState(String(filters.per_page));
    const hasHistoryFilters =
        filters.history_search !== '' ||
        filters.date_from !== '' ||
        filters.date_to !== '';

    usePoll(30_000, { only: ['recentCheckIns', 'todayCount'] });

    function submitMemberSearch(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        visit({ member_search: memberSearch }, [
            'memberSearchResults',
            'filters',
        ]);
    }

    function submitHistoryFilters(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        visit(
            {
                history_search: historySearch,
                date_from: dateFrom,
                date_to: dateTo,
                per_page: perPage,
            },
            ['history', 'filters'],
        );
    }

    function clearHistoryFilters() {
        setHistorySearch('');
        setDateFrom('');
        setDateTo('');
        setPerPage('15');
        visit(
            {
                history_search: '',
                date_from: '',
                date_to: '',
                per_page: '15',
            },
            ['history', 'filters'],
        );
    }

    function visit(
        changes: Partial<Record<keyof typeof filters, string>>,
        only: string[],
    ) {
        router.get(
            CheckInController.index.url(),
            {
                member_search: memberSearch,
                history_search: historySearch,
                date_from: dateFrom,
                date_to: dateTo,
                per_page: perPage,
                ...changes,
            },
            { preserveState: true, replace: true, only },
        );
    }

    return (
        <>
            <Head title="Check-in" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {todayCount} check-in hari ini
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                            Check-in member
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Temukan member, validasi membership, lalu catat
                            kunjungan.
                        </p>
                    </div>
                    <p className="inline-flex items-center gap-2 text-xs text-muted-foreground">
                        <Clock3 className="size-4" />
                        Proteksi duplikat {duplicateWindowMinutes} menit
                    </p>
                </header>

                <div className="grid items-start gap-8 lg:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.65fr)]">
                    <section className="min-w-0">
                        <div>
                            <h2 className="text-base font-semibold">
                                Cari member
                            </h2>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Gunakan nama, nomor member, atau nomor telepon.
                            </p>
                        </div>

                        <form
                            onSubmit={submitMemberSearch}
                            className="mt-4 flex gap-2"
                            aria-label="Pencarian member untuk check-in"
                        >
                            <div className="relative min-w-0 flex-1">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={memberSearch}
                                    onChange={(event) =>
                                        setMemberSearch(event.target.value)
                                    }
                                    placeholder="Nama, nomor member, atau telepon"
                                    className="pl-9"
                                    aria-label="Cari member untuk check-in"
                                    data-test="check-in-member-search"
                                    autoFocus
                                />
                            </div>
                            <Button type="submit">
                                <Search />
                                Cari
                            </Button>
                        </form>

                        <MemberSearchResults
                            search={filters.member_search}
                            members={memberSearchResults}
                            timezone={timezone}
                        />
                    </section>

                    <section className="min-w-0 border-y py-5 lg:border-y-0 lg:border-l lg:py-0 lg:pl-8">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <h2 className="text-base font-semibold">
                                    Check-in terbaru
                                </h2>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Diperbarui otomatis setiap 30 detik.
                                </p>
                            </div>
                            <CheckCircle2 className="size-5 text-emerald-600" />
                        </div>

                        {recentCheckIns.length === 0 ? (
                            <p className="mt-5 border-y py-6 text-sm text-muted-foreground">
                                Belum ada check-in tercatat.
                            </p>
                        ) : (
                            <div className="mt-4 divide-y border-y">
                                {recentCheckIns.map((checkIn) => (
                                    <RecentCheckIn
                                        key={checkIn.id}
                                        checkIn={checkIn}
                                        timezone={timezone}
                                    />
                                ))}
                            </div>
                        )}
                    </section>
                </div>

                <section className="min-w-0 border-t pt-7">
                    <div>
                        <h2 className="text-base font-semibold">
                            Riwayat check-in
                        </h2>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {history.total} kunjungan tersimpan
                        </p>
                    </div>

                    <form
                        onSubmit={submitHistoryFilters}
                        className="mt-5 grid gap-3 border-y py-4 md:grid-cols-2 xl:grid-cols-[minmax(14rem,1fr)_11rem_11rem_8rem_auto]"
                        aria-label="Filter riwayat check-in"
                    >
                        <div className="relative">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={historySearch}
                                onChange={(event) =>
                                    setHistorySearch(event.target.value)
                                }
                                placeholder="Cari member"
                                className="pl-9"
                                aria-label="Cari riwayat check-in"
                            />
                        </div>
                        <Input
                            type="date"
                            value={dateFrom}
                            onChange={(event) =>
                                setDateFrom(event.target.value)
                            }
                            aria-label="Tanggal check-in mulai"
                        />
                        <Input
                            type="date"
                            value={dateTo}
                            min={dateFrom || undefined}
                            onChange={(event) => setDateTo(event.target.value)}
                            aria-label="Tanggal check-in akhir"
                        />
                        <select
                            value={perPage}
                            onChange={(event) => setPerPage(event.target.value)}
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            aria-label="Jumlah riwayat per halaman"
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
                            {hasHistoryFilters && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    onClick={clearHistoryFilters}
                                    title="Hapus filter"
                                >
                                    <FilterX />
                                    <span className="sr-only">
                                        Hapus filter
                                    </span>
                                </Button>
                            )}
                        </div>
                    </form>

                    <CheckInHistory
                        history={history}
                        timezone={timezone}
                        hasFilters={hasHistoryFilters}
                    />
                </section>
            </div>
        </>
    );
}

function MemberSearchResults({
    search,
    members,
    timezone,
}: {
    search: string;
    members: CheckInMemberSearchResult[];
    timezone: string;
}) {
    if (search === '') {
        return (
            <div className="mt-5 flex min-h-44 flex-col items-center justify-center border-y px-6 py-8 text-center">
                <UserRoundSearch className="size-8 text-muted-foreground" />
                <p className="mt-3 text-sm font-medium">Siap mencari member</p>
                <p className="mt-1 text-xs text-muted-foreground">
                    Hasil dan status membership akan tampil di sini.
                </p>
            </div>
        );
    }

    if (members.length === 0) {
        return (
            <div className="mt-5 flex min-h-44 flex-col items-center justify-center border-y px-6 py-8 text-center">
                <UserRoundSearch className="size-8 text-muted-foreground" />
                <p className="mt-3 text-sm font-medium">
                    Member tidak ditemukan
                </p>
                <p className="mt-1 text-xs text-muted-foreground">
                    Periksa nama, nomor member, atau nomor telepon.
                </p>
            </div>
        );
    }

    return (
        <div className="mt-5 divide-y border-y">
            {members.map((member) => (
                <article
                    key={member.id}
                    className="flex flex-col justify-between gap-4 py-4 sm:flex-row sm:items-center"
                    data-test={`check-in-search-result-${member.id}`}
                >
                    <div className="flex min-w-0 items-start gap-3">
                        <MemberAvatar
                            name={member.name}
                            photoUrl={member.photo_url}
                            className="size-10"
                        />
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <Link
                                    href={MemberController.show(member.id)}
                                    className="truncate text-sm font-medium hover:text-primary"
                                >
                                    {member.name}
                                </Link>
                                <MemberStatusBadge
                                    status={member.status}
                                    label={member.status_label}
                                />
                            </div>
                            <p className="mt-1 font-mono text-xs text-muted-foreground">
                                {member.member_number} · {member.phone}
                            </p>
                            {member.membership ? (
                                <p className="mt-2 text-xs text-emerald-700 dark:text-emerald-300">
                                    {member.membership.plan_name} aktif hingga{' '}
                                    {formatDate(member.membership.end_date)}
                                </p>
                            ) : (
                                <p className="mt-2 text-xs text-amber-700 dark:text-amber-300">
                                    Tidak ada membership aktif hari ini.
                                </p>
                            )}
                            {!member.eligibility.can_check_in &&
                                member.eligibility.reason && (
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {member.eligibility.reason}
                                        {member.eligibility.latest_check_in_at
                                            ? ` Terakhir ${formatDateTime(member.eligibility.latest_check_in_at, timezone)}.`
                                            : ''}
                                    </p>
                                )}
                        </div>
                    </div>
                    <div className="flex shrink-0 justify-end">
                        {member.eligibility.can_check_in ? (
                            <CheckInButton memberId={member.id} />
                        ) : member.membership === null ? (
                            <Button size="sm" variant="outline" asChild>
                                <Link href={MemberController.show(member.id)}>
                                    Kelola membership
                                </Link>
                            </Button>
                        ) : (
                            <CheckInButton
                                memberId={member.id}
                                disabled
                                disabledReason={member.eligibility.reason}
                            />
                        )}
                    </div>
                </article>
            ))}
        </div>
    );
}

function RecentCheckIn({
    checkIn,
    timezone,
}: {
    checkIn: CheckInListItem;
    timezone: string;
}) {
    return (
        <article className="flex items-start gap-3 py-3">
            <MemberAvatar
                name={checkIn.member.name}
                photoUrl={checkIn.member.photo_url}
                className="size-8"
            />
            <div className="min-w-0 flex-1">
                <Link
                    href={MemberController.show(checkIn.member.id)}
                    className="block truncate text-sm font-medium hover:text-primary"
                >
                    {checkIn.member.name}
                </Link>
                <p className="mt-0.5 truncate text-xs text-muted-foreground">
                    {checkIn.membership.plan_name} ·{' '}
                    {formatDateTime(checkIn.checked_in_at, timezone)}
                </p>
            </div>
        </article>
    );
}

function CheckInHistory({
    history,
    timezone,
    hasFilters,
}: {
    history: CheckInIndexProps['history'];
    timezone: string;
    hasFilters: boolean;
}) {
    if (history.data.length === 0) {
        return (
            <div className="flex min-h-56 flex-col items-center justify-center px-6 py-10 text-center">
                <History className="size-8 text-muted-foreground" />
                <p className="mt-3 text-sm font-medium">
                    {hasFilters
                        ? 'Riwayat tidak ditemukan'
                        : 'Belum ada riwayat check-in'}
                </p>
            </div>
        );
    }

    return (
        <>
            <div className="mt-5 hidden overflow-hidden rounded-lg border md:block">
                <table className="w-full text-left text-sm">
                    <thead className="border-b bg-muted/50 text-xs text-muted-foreground">
                        <tr>
                            <th className="px-4 py-3 font-medium">Waktu</th>
                            <th className="px-4 py-3 font-medium">Member</th>
                            <th className="px-4 py-3 font-medium">
                                Membership
                            </th>
                            <th className="px-4 py-3 font-medium">Petugas</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {history.data.map((checkIn) => (
                            <tr key={checkIn.id} className="hover:bg-muted/30">
                                <td className="px-4 py-3 text-xs tabular-nums">
                                    {formatDateTime(
                                        checkIn.checked_in_at,
                                        timezone,
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    <Link
                                        href={MemberController.show(
                                            checkIn.member.id,
                                        )}
                                        className="font-medium hover:text-primary"
                                    >
                                        {checkIn.member.name}
                                    </Link>
                                    <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                                        {checkIn.member.member_number}
                                    </p>
                                </td>
                                <td className="px-4 py-3">
                                    <p className="font-medium">
                                        {checkIn.membership.plan_name}
                                    </p>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        Berlaku s.d.{' '}
                                        {formatDate(
                                            checkIn.membership.end_date,
                                        )}
                                    </p>
                                </td>
                                <td className="px-4 py-3 text-xs text-muted-foreground">
                                    {checkIn.created_by?.name ?? 'Akun dihapus'}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="mt-5 divide-y border-y md:hidden">
                {history.data.map((checkIn) => (
                    <article key={checkIn.id} className="py-4">
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <Link
                                    href={MemberController.show(
                                        checkIn.member.id,
                                    )}
                                    className="block truncate font-medium hover:text-primary"
                                >
                                    {checkIn.member.name}
                                </Link>
                                <p className="mt-1 font-mono text-xs text-muted-foreground">
                                    {checkIn.member.member_number}
                                </p>
                            </div>
                            <CalendarClock className="size-4 shrink-0 text-muted-foreground" />
                        </div>
                        <p className="mt-3 text-sm">
                            {checkIn.membership.plan_name}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {formatDateTime(checkIn.checked_in_at, timezone)} ·{' '}
                            {checkIn.created_by?.name ?? 'Akun dihapus'}
                        </p>
                    </article>
                ))}
            </div>

            {history.last_page > 1 && (
                <nav
                    className="flex items-center justify-between gap-4 border-t pt-4"
                    aria-label="Pagination riwayat check-in"
                >
                    <p className="hidden text-sm text-muted-foreground sm:block">
                        {history.from}-{history.to} dari {history.total}
                    </p>
                    <div className="flex w-full items-center justify-between gap-1 sm:w-auto sm:justify-end">
                        <PaginationButton
                            url={history.links[0]?.url ?? null}
                            label="Sebelumnya"
                            icon={<ChevronLeft />}
                        />
                        <div className="hidden gap-1 sm:flex">
                            {history.links.slice(1, -1).map((link) => (
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
                                        <Link href={link.url} preserveScroll>
                                            {link.label}
                                        </Link>
                                    ) : (
                                        <span>{link.label}</span>
                                    )}
                                </Button>
                            ))}
                        </div>
                        <PaginationButton
                            url={history.links.at(-1)?.url ?? null}
                            label="Berikutnya"
                            icon={<ChevronRight />}
                            iconAfter
                        />
                    </div>
                </nav>
            )}
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

CheckInsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Check-in',
            href: CheckInController.index(),
        },
    ],
};
