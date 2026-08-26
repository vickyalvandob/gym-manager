import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarClock, Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import PtSessionController from '@/actions/App/Http/Controllers/PtSessionController';
import { PtSessionStatusBadge } from '@/components/personal-training/pt-session-status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDateTime } from '@/lib/formatters';
import type { PaginatedPtSessions, SelectOption } from '@/types';

type Filters = {
    scope: string;
    trainer_id: number | null;
    status: string;
    date: string;
    per_page: number;
};

export default function PtSessionsIndex({
    sessions,
    filters,
    trainerOptions,
    statusOptions,
    isTrainer,
}: {
    sessions: PaginatedPtSessions;
    filters: Filters;
    trainerOptions: Array<SelectOption & { value: number }>;
    statusOptions: SelectOption[];
    isTrainer: boolean;
}) {
    const { auth } = usePage().props;
    const [trainerId, setTrainerId] = useState(
        filters.trainer_id?.toString() ?? '',
    );
    const [status, setStatus] = useState(filters.status);
    const [date, setDate] = useState(filters.date);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get(
            PtSessionController.index.url(),
            {
                scope: filters.scope,
                trainer_id: trainerId,
                status,
                date,
                per_page: filters.per_page,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Sesi PT" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">
                        {sessions.total} sesi
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold">
                        {isTrainer ? 'Jadwal Saya' : 'Sesi Personal Training'}
                    </h1>
                </header>
                <nav className="flex gap-1 border-b" aria-label="Rentang sesi">
                    {[
                        ['today', 'Hari ini'],
                        ['upcoming', 'Mendatang'],
                        ['history', 'Riwayat'],
                    ].map(([value, label]) => (
                        <Button
                            key={value}
                            variant="ghost"
                            className={`rounded-none border-b-2 ${filters.scope === value ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground'}`}
                            asChild
                        >
                            <Link
                                href={PtSessionController.index({
                                    query: { scope: value },
                                })}
                            >
                                {label}
                            </Link>
                        </Button>
                    ))}
                </nav>
                <form
                    onSubmit={submit}
                    className="grid gap-3 border-y py-4 md:grid-cols-[1fr_1fr_1fr_auto]"
                >
                    {!isTrainer ? (
                        <select
                            value={trainerId}
                            onChange={(event) =>
                                setTrainerId(event.target.value)
                            }
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">Semua trainer</option>
                            {trainerOptions.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </select>
                    ) : (
                        <div className="hidden md:block" />
                    )}
                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="">Semua status</option>
                        {statusOptions.map((item) => (
                            <option key={item.value} value={item.value}>
                                {item.label}
                            </option>
                        ))}
                    </select>
                    <Input
                        type="date"
                        value={date}
                        onChange={(event) => setDate(event.target.value)}
                        aria-label="Tanggal sesi"
                    />
                    <Button type="submit" variant="outline">
                        <Search />
                        Terapkan
                    </Button>
                </form>
                {sessions.data.length === 0 ? (
                    <div className="flex min-h-64 flex-col items-center justify-center border-y text-center">
                        <CalendarClock className="size-8 text-muted-foreground" />
                        <p className="mt-3 text-sm font-medium">
                            Tidak ada sesi pada filter ini.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="hidden overflow-hidden rounded-lg border md:block">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/50 text-xs text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Jadwal
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Member
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Trainer
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Paket
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {sessions.data.map((session) => (
                                        <tr
                                            key={session.id}
                                            className="hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={PtSessionController.show(
                                                        session.id,
                                                    )}
                                                    className="font-medium hover:text-primary"
                                                >
                                                    {formatDateTime(
                                                        session.scheduled_at,
                                                        auth.currentGym
                                                            ?.timezone,
                                                    )}
                                                </Link>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {session.duration_minutes}{' '}
                                                    menit
                                                </p>
                                            </td>
                                            <td className="px-4 py-3">
                                                <p className="font-medium">
                                                    {session.member.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {
                                                        session.member
                                                            .member_number
                                                    }
                                                </p>
                                            </td>
                                            <td className="px-4 py-3">
                                                {session.trainer.name}
                                            </td>
                                            <td className="px-4 py-3">
                                                {session.pt_package.name}
                                            </td>
                                            <td className="px-4 py-3">
                                                <PtSessionStatusBadge
                                                    status={session.status}
                                                    label={session.status_label}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="grid gap-3 md:hidden">
                            {sessions.data.map((session) => (
                                <Link
                                    key={session.id}
                                    href={PtSessionController.show(session.id)}
                                    className="rounded-lg border p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="font-medium">
                                                {session.member.name}
                                            </p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {formatDateTime(
                                                    session.scheduled_at,
                                                    auth.currentGym?.timezone,
                                                )}{' '}
                                                · {session.duration_minutes}{' '}
                                                menit
                                            </p>
                                        </div>
                                        <PtSessionStatusBadge
                                            status={session.status}
                                            label={session.status_label}
                                        />
                                    </div>
                                    <p className="mt-3 text-sm text-muted-foreground">
                                        {session.trainer.name} ·{' '}
                                        {session.pt_package.name}
                                    </p>
                                </Link>
                            ))}
                        </div>
                    </>
                )}
                {sessions.last_page > 1 && (
                    <nav className="flex items-center justify-between border-t pt-4">
                        <p className="text-sm text-muted-foreground">
                            {sessions.from}-{sessions.to} dari {sessions.total}
                        </p>
                        <div className="flex gap-2">
                            {[sessions.links[0], sessions.links.at(-1)].map(
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

PtSessionsIndex.layout = {
    breadcrumbs: [{ title: 'Sesi PT', href: PtSessionController.index() }],
};
