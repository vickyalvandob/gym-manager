import { Deferred, Head } from '@inertiajs/react';
import { CalendarClock, Clock3, Dumbbell, UserRound } from 'lucide-react';
import {
    PortalDataError,
    PortalDataSkeleton,
} from '@/components/member-portal/portal-deferred-state';
import { PortalPageHeader } from '@/components/member-portal/portal-page-header';
import { PortalPagination } from '@/components/member-portal/portal-pagination';
import { PaymentStatusBadge } from '@/components/payments/payment-status-badge';
import { PtSessionStatusBadge } from '@/components/personal-training/pt-session-status-badge';
import { Badge } from '@/components/ui/badge';
import { formatDate, formatDateTime } from '@/lib/formatters';
import type {
    MemberPortalPersonalTrainingProps,
    MemberPortalPtPackage,
} from '@/types';

export default function MemberPortalPersonalTraining({
    member,
    ptPackages,
    ptSessions,
}: MemberPortalPersonalTrainingProps) {
    const timezone = member.gym.timezone;

    return (
        <div className="mx-auto max-w-6xl space-y-7">
            <Head title="Personal Training" />
            <PortalPageHeader
                eyebrow="Latihan"
                title="Personal Training"
                description="Lihat paket, sisa sesi, trainer, dan jadwal latihan pribadi Anda."
            />

            <Deferred
                data={['ptPackages', 'ptSessions']}
                fallback={<PortalTrainingSkeleton />}
                rescue={({ reloading }) => (
                    <PortalDataError
                        reloading={reloading}
                        only={['ptPackages', 'ptSessions']}
                    />
                )}
            >
                {ptPackages && ptSessions && (
                    <div className="space-y-6">
                        <section className="overflow-hidden rounded-xl border bg-background">
                            <div className="flex items-start gap-3 border-b px-5 py-5 sm:px-6">
                                <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <Dumbbell className="size-4" />
                                </span>
                                <div>
                                    <h2 className="text-base font-semibold">
                                        Paket PT
                                    </h2>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        Total {ptPackages.total} paket tercatat
                                    </p>
                                </div>
                            </div>

                            {ptPackages.data.length === 0 ? (
                                <EmptyTraining message="Belum ada paket Personal Training." />
                            ) : (
                                <div className="grid gap-4 p-5 sm:p-6 md:grid-cols-2">
                                    {ptPackages.data.map((packageItem) => (
                                        <PackageCard
                                            key={packageItem.id}
                                            packageItem={packageItem}
                                            timezone={timezone}
                                        />
                                    ))}
                                </div>
                            )}
                            <PortalPagination paginator={ptPackages} />
                        </section>

                        <section className="overflow-hidden rounded-xl border bg-background">
                            <div className="flex items-start gap-3 border-b px-5 py-5 sm:px-6">
                                <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <CalendarClock className="size-4" />
                                </span>
                                <div>
                                    <h2 className="text-base font-semibold">
                                        Jadwal sesi
                                    </h2>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        Jadwal terbaru dan status pelaksanaan
                                        sesi
                                    </p>
                                </div>
                            </div>

                            {ptSessions.data.length === 0 ? (
                                <EmptyTraining message="Belum ada jadwal sesi Personal Training." />
                            ) : (
                                <div className="divide-y">
                                    {ptSessions.data.map((session) => (
                                        <article
                                            key={session.id}
                                            className="grid gap-4 px-5 py-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-6"
                                        >
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="truncate text-sm font-semibold">
                                                        {
                                                            session.pt_package_name
                                                        }
                                                    </p>
                                                    <PtSessionStatusBadge
                                                        status={session.status}
                                                        label={
                                                            session.status_label
                                                        }
                                                    />
                                                </div>
                                                <p className="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                                                    <UserRound className="size-3.5" />
                                                    {session.trainer.name}
                                                    {session.trainer
                                                        .specialization &&
                                                        ` · ${session.trainer.specialization}`}
                                                </p>
                                            </div>
                                            <div className="text-sm sm:text-right">
                                                <p className="font-medium">
                                                    {formatDateTime(
                                                        session.scheduled_at,
                                                        timezone,
                                                    )}
                                                </p>
                                                <p className="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground sm:justify-end">
                                                    <Clock3 className="size-3.5" />
                                                    {session.duration_minutes}{' '}
                                                    menit
                                                </p>
                                            </div>
                                        </article>
                                    ))}
                                </div>
                            )}
                            <PortalPagination paginator={ptSessions} />
                        </section>
                    </div>
                )}
            </Deferred>
        </div>
    );
}

function PackageCard({
    packageItem,
    timezone,
}: {
    packageItem: MemberPortalPtPackage;
    timezone: string;
}) {
    return (
        <article className="rounded-xl border p-5">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <h3 className="truncate font-semibold">
                        {packageItem.name}
                    </h3>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {packageItem.trainer.name}
                        {packageItem.trainer.specialization &&
                            ` · ${packageItem.trainer.specialization}`}
                    </p>
                </div>
                <Badge variant="outline">{packageItem.status_label}</Badge>
            </div>

            <div className="mt-5 grid grid-cols-3 gap-2">
                <Metric
                    label="Tersedia"
                    value={packageItem.available_sessions}
                />
                <Metric label="Terpakai" value={packageItem.used_sessions} />
                <Metric
                    label="Terjadwal"
                    value={packageItem.scheduled_sessions}
                />
            </div>

            <div className="mt-5 flex flex-wrap items-end justify-between gap-3 border-t pt-4">
                <div>
                    <p className="text-xs text-muted-foreground">
                        Masa berlaku
                    </p>
                    <p className="mt-1 text-xs font-medium">
                        {formatDate(packageItem.start_date, timezone)} –{' '}
                        {packageItem.expires_at
                            ? formatDate(packageItem.expires_at, timezone)
                            : 'Tanpa batas'}
                    </p>
                </div>
                <PaymentStatusBadge
                    status={packageItem.payment_status}
                    label={packageItem.payment_status_label}
                />
            </div>
        </article>
    );
}

function Metric({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-lg bg-muted/50 px-2 py-3 text-center">
            <p className="text-lg font-semibold tabular-nums">{value}</p>
            <p className="mt-0.5 text-[11px] text-muted-foreground">{label}</p>
        </div>
    );
}

function EmptyTraining({ message }: { message: string }) {
    return (
        <div className="px-6 py-14 text-center">
            <Dumbbell className="mx-auto size-9 text-muted-foreground" />
            <p className="mt-4 text-sm font-semibold">{message}</p>
            <p className="mt-1 text-xs text-muted-foreground">
                Hubungi Front Desk untuk informasi lebih lanjut.
            </p>
        </div>
    );
}

function PortalTrainingSkeleton() {
    return (
        <div className="space-y-6">
            <PortalDataSkeleton rows={2} />
            <PortalDataSkeleton rows={4} />
        </div>
    );
}
