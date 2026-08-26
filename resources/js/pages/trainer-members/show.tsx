import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, CalendarClock, Phone } from 'lucide-react';
import PtSessionController from '@/actions/App/Http/Controllers/PtSessionController';
import TrainerMemberController from '@/actions/App/Http/Controllers/TrainerMemberController';
import { PtScheduleDialog } from '@/components/personal-training/pt-schedule-dialog';
import { PtSessionStatusBadge } from '@/components/personal-training/pt-session-status-badge';
import { Button } from '@/components/ui/button';
import { formatDate, formatDateTime } from '@/lib/formatters';
import type { TrainerWorkspaceMember } from '@/types';

export default function ShowTrainerMember({
    member,
    scheduleDate,
}: {
    member: TrainerWorkspaceMember;
    scheduleDate: string;
}) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title={member.name} />
            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-7 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex items-start justify-between gap-4">
                    <div className="flex items-start gap-3">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={TrainerMemberController.index()}>
                                <ArrowLeft />
                                <span className="sr-only">Kembali</span>
                            </Link>
                        </Button>
                        <div>
                            <p className="text-sm font-medium text-primary">
                                {member.member_number}
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold">
                                {member.name}
                            </h1>
                            <p className="mt-2 flex items-center gap-2 text-sm text-muted-foreground">
                                <Phone className="size-4" />
                                {member.phone}
                            </p>
                        </div>
                    </div>
                    {member.pt_package && (
                        <PtScheduleDialog
                            memberPtPackageId={member.pt_package.id}
                            memberName={member.name}
                            packageName={member.pt_package.name}
                            availableSessions={
                                member.pt_package.available_sessions
                            }
                            defaultDate={scheduleDate}
                        />
                    )}
                </header>
                <div className="grid gap-6 md:grid-cols-2">
                    <section className="border-y py-5">
                        <h2 className="text-base font-semibold">Membership</h2>
                        {member.membership ? (
                            <dl className="mt-4 space-y-3 text-sm">
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Paket
                                    </dt>
                                    <dd className="mt-1 font-medium">
                                        {member.membership.plan_name}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Berlaku sampai
                                    </dt>
                                    <dd className="mt-1 font-medium">
                                        {formatDate(member.membership.end_date)}
                                    </dd>
                                </div>
                            </dl>
                        ) : (
                            <p className="mt-3 text-sm text-muted-foreground">
                                Membership tidak aktif.
                            </p>
                        )}
                    </section>
                    <section className="border-y py-5">
                        <h2 className="text-base font-semibold">
                            Quota Personal Training
                        </h2>
                        {member.pt_package ? (
                            <>
                                <p className="mt-4 text-3xl font-semibold">
                                    {member.pt_package.available_sessions}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    sesi tersedia dari{' '}
                                    {member.pt_package.total_sessions}
                                </p>
                                <div className="mt-4 h-2 overflow-hidden rounded-full bg-muted">
                                    <div
                                        className="h-full bg-primary"
                                        style={{
                                            width: `${Math.min(100, (member.pt_package.used_sessions / member.pt_package.total_sessions) * 100)}%`,
                                        }}
                                    />
                                </div>
                                <p className="mt-3 text-xs text-muted-foreground">
                                    {member.pt_package.name} · berlaku sampai{' '}
                                    {formatDate(member.pt_package.expires_at)}
                                </p>
                            </>
                        ) : (
                            <p className="mt-3 text-sm text-muted-foreground">
                                Tidak ada paket PT aktif.
                            </p>
                        )}
                    </section>
                </div>
                <section className="border-y py-6">
                    <div className="flex items-center gap-2">
                        <CalendarClock className="size-5 text-muted-foreground" />
                        <h2 className="text-base font-semibold">
                            Riwayat sesi
                        </h2>
                    </div>
                    {!member.sessions?.length ? (
                        <p className="mt-4 text-sm text-muted-foreground">
                            Belum ada sesi.
                        </p>
                    ) : (
                        <div className="mt-5 divide-y border-y">
                            {member.sessions.map((session) => (
                                <Link
                                    key={session.id}
                                    href={PtSessionController.show(session.id)}
                                    className="flex items-center justify-between gap-4 py-4 hover:text-primary"
                                >
                                    <div>
                                        <p className="text-sm font-medium">
                                            {formatDateTime(
                                                session.scheduled_at,
                                                auth.currentGym?.timezone,
                                            )}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {session.pt_package_name} ·{' '}
                                            {session.duration_minutes} menit
                                        </p>
                                    </div>
                                    <PtSessionStatusBadge
                                        status={session.status}
                                        label={session.status_label}
                                    />
                                </Link>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}
ShowTrainerMember.layout = {
    breadcrumbs: [
        { title: 'Member Saya', href: TrainerMemberController.index() },
        { title: 'Detail member', href: '#' },
    ],
};
