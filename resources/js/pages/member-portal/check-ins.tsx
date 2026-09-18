import { Deferred, Head } from '@inertiajs/react';
import { CalendarDays, CheckCircle2, History } from 'lucide-react';
import {
    PortalDataError,
    PortalDataSkeleton,
} from '@/components/member-portal/portal-deferred-state';
import { PortalPageHeader } from '@/components/member-portal/portal-page-header';
import { PortalPagination } from '@/components/member-portal/portal-pagination';
import { formatDate, formatDateTime } from '@/lib/formatters';
import type { MemberPortalCheckInsProps } from '@/types';

export default function MemberPortalCheckIns({
    member,
    checkIns,
}: MemberPortalCheckInsProps) {
    const timezone = member.gym.timezone;

    return (
        <div className="mx-auto max-w-5xl space-y-7">
            <Head title="Riwayat Check-in" />
            <PortalPageHeader
                eyebrow="Aktivitas"
                title="Riwayat check-in"
                description="Semua kunjungan yang berhasil dicatat oleh Front Office tersimpan di halaman ini."
            />

            <Deferred
                data="checkIns"
                fallback={<PortalDataSkeleton />}
                rescue={({ reloading }) => (
                    <PortalDataError
                        reloading={reloading}
                        only={['checkIns']}
                    />
                )}
            >
                {checkIns && (
                    <section className="overflow-hidden rounded-xl border bg-background">
                        <div className="flex items-start gap-3 border-b px-5 py-5 sm:px-6">
                            <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <History className="size-4" />
                            </span>
                            <div>
                                <h2 className="text-base font-semibold">
                                    Catatan kunjungan
                                </h2>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    Total {checkIns.total} check-in tercatat
                                </p>
                            </div>
                        </div>

                        {checkIns.data.length === 0 ? (
                            <div className="px-6 py-16 text-center">
                                <CheckCircle2 className="mx-auto size-9 text-muted-foreground" />
                                <p className="mt-4 text-sm font-semibold">
                                    Belum ada riwayat check-in
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Kunjungan pertama Anda akan muncul di sini.
                                </p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {checkIns.data.map((checkIn) => (
                                    <article
                                        key={checkIn.id}
                                        className="grid gap-4 px-5 py-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-6"
                                    >
                                        <div className="flex min-w-0 items-start gap-3">
                                            <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                                <CheckCircle2 className="size-4" />
                                            </span>
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-semibold">
                                                    {
                                                        checkIn.membership
                                                            .plan_name
                                                    }
                                                </p>
                                                <p className="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                                                    <CalendarDays className="size-3.5" />
                                                    Membership sampai{' '}
                                                    {formatDate(
                                                        checkIn.membership
                                                            .end_date,
                                                        timezone,
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                        <time className="pl-11 text-sm font-medium sm:pl-0 sm:text-right">
                                            {formatDateTime(
                                                checkIn.checked_in_at,
                                                timezone,
                                            )}
                                        </time>
                                    </article>
                                ))}
                            </div>
                        )}

                        <PortalPagination paginator={checkIns} />
                    </section>
                )}
            </Deferred>
        </div>
    );
}
