import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarClock, Search, UsersRound } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import TrainerMemberController from '@/actions/App/Http/Controllers/TrainerMemberController';
import { PtScheduleDialog } from '@/components/personal-training/pt-schedule-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate, formatDateTime } from '@/lib/formatters';
import type { PaginatedTrainerWorkspaceMembers } from '@/types';

export default function TrainerMembersIndex({
    members,
    filters,
    scheduleDate,
}: {
    members: PaginatedTrainerWorkspaceMembers;
    filters: { search: string; per_page: number };
    scheduleDate: string;
}) {
    const { auth } = usePage().props;
    const [search, setSearch] = useState(filters.search);
    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get(
            TrainerMemberController.index.url(),
            { search, per_page: filters.per_page },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Member Saya" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">
                        {members.total} member aktif
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold">Member Saya</h1>
                </header>
                <form onSubmit={submit} className="flex gap-2 border-y py-4">
                    <div className="relative flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            className="pl-9"
                            placeholder="Nama, nomor member, atau telepon"
                        />
                    </div>
                    <Button type="submit" variant="outline">
                        <Search />
                        Cari
                    </Button>
                </form>
                {members.data.length === 0 ? (
                    <div className="flex min-h-64 flex-col items-center justify-center border-y">
                        <UsersRound className="size-8 text-muted-foreground" />
                        <p className="mt-3 text-sm font-medium">
                            Belum ada member yang ditugaskan.
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-3 lg:grid-cols-2">
                        {members.data.map((member) => (
                            <article
                                key={member.id}
                                className="rounded-lg border p-5"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <Link
                                            href={TrainerMemberController.show(
                                                member.id,
                                            )}
                                            className="font-semibold hover:text-primary"
                                        >
                                            {member.name}
                                        </Link>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {member.member_number} ·{' '}
                                            {member.phone}
                                        </p>
                                    </div>
                                    {member.pt_package && (
                                        <PtScheduleDialog
                                            memberPtPackageId={
                                                member.pt_package.id
                                            }
                                            memberName={member.name}
                                            packageName={member.pt_package.name}
                                            availableSessions={
                                                member.pt_package
                                                    .available_sessions
                                            }
                                            defaultDate={scheduleDate}
                                        />
                                    )}
                                </div>
                                <dl className="mt-5 grid gap-4 border-t pt-4 sm:grid-cols-2">
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            Membership
                                        </dt>
                                        <dd className="mt-1 text-sm font-medium">
                                            {member.membership
                                                ? `${member.membership.plan_name} · ${formatDate(member.membership.end_date)}`
                                                : 'Tidak aktif'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            Paket PT
                                        </dt>
                                        <dd className="mt-1 text-sm font-medium">
                                            {member.pt_package
                                                ? `${member.pt_package.available_sessions} dari ${member.pt_package.total_sessions} sesi tersedia`
                                                : 'Tidak ada paket aktif'}
                                        </dd>
                                    </div>
                                </dl>
                                {member.next_session && (
                                    <Link
                                        href={TrainerMemberController.show(
                                            member.id,
                                        )}
                                        className="mt-4 flex items-center gap-3 rounded-md bg-muted/40 p-3 text-sm"
                                    >
                                        <CalendarClock className="size-4 text-primary" />
                                        <div>
                                            <p className="font-medium">
                                                Sesi berikutnya
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {formatDateTime(
                                                    member.next_session
                                                        .scheduled_at,
                                                    auth.currentGym?.timezone,
                                                )}{' '}
                                                ·{' '}
                                                {
                                                    member.next_session
                                                        .duration_minutes
                                                }{' '}
                                                menit
                                            </p>
                                        </div>
                                    </Link>
                                )}
                            </article>
                        ))}
                    </div>
                )}
                {members.last_page > 1 && (
                    <nav className="flex justify-between border-t pt-4">
                        <span className="text-sm text-muted-foreground">
                            {members.from}-{members.to} dari {members.total}
                        </span>
                        <div className="flex gap-2">
                            {[members.links[0], members.links.at(-1)].map(
                                (link, index) => (
                                    <Button
                                        key={index}
                                        variant="outline"
                                        size="sm"
                                        disabled={!link?.url}
                                        asChild={Boolean(link?.url)}
                                    >
                                        {link?.url ? (
                                            <Link
                                                href={link.url}
                                                preserveScroll
                                            >
                                                {index
                                                    ? 'Berikutnya'
                                                    : 'Sebelumnya'}
                                            </Link>
                                        ) : (
                                            <span>
                                                {index
                                                    ? 'Berikutnya'
                                                    : 'Sebelumnya'}
                                            </span>
                                        )}
                                    </Button>
                                ),
                            )}
                        </div>
                    </nav>
                )}
            </div>
        </>
    );
}
TrainerMembersIndex.layout = {
    breadcrumbs: [
        { title: 'Member Saya', href: TrainerMemberController.index() },
    ],
};
