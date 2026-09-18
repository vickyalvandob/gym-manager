import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    CheckCircle2,
    CreditCard,
    Dumbbell,
    History,
    QrCode,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { PortalPageHeader } from '@/components/member-portal/portal-page-header';
import { MemberMembershipStatusBadge } from '@/components/members/member-membership-status-badge';
import { PaymentStatusBadge } from '@/components/payments/payment-status-badge';
import { Button } from '@/components/ui/button';
import { formatDate, formatDateTime } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import MemberPortalController from '@/routes/my';
import type { MemberPortalOverviewProps } from '@/types';

export default function MemberPortalOverview({
    member,
    membership,
    latest_check_in_at: latestCheckInAt,
    active_pt_package: activePtPackage,
    qr_code_data_url: qrCodeDataUrl,
}: MemberPortalOverviewProps) {
    const timezone = member.gym.timezone;

    return (
        <div className="mx-auto max-w-6xl space-y-7">
            <Head title="Beranda Member" />
            <PortalPageHeader
                eyebrow="Portal member"
                title={`Halo, ${member.name}`}
                description="Gunakan QR untuk check-in dan lihat status membership Anda dalam satu tampilan ringkas."
            />

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(20rem,0.85fr)]">
                <section className="overflow-hidden rounded-2xl border bg-background">
                    <div className="flex items-center justify-between gap-4 border-b px-5 py-4 sm:px-6">
                        <div className="flex items-center gap-3">
                            <span className="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <QrCode className="size-4" />
                            </span>
                            <div>
                                <h2 className="text-sm font-semibold">
                                    QR Check-in
                                </h2>
                                <p className="text-xs text-muted-foreground">
                                    Kartu member digital Anda
                                </p>
                            </div>
                        </div>
                        <span className="rounded-full border px-2.5 py-1 font-mono text-xs text-muted-foreground">
                            {member.member_number}
                        </span>
                    </div>

                    <div className="grid gap-6 p-5 sm:grid-cols-[minmax(15rem,0.85fr)_minmax(0,1fr)] sm:items-center sm:p-7">
                        <div className="mx-auto flex aspect-square w-full max-w-72 items-center justify-center rounded-2xl border bg-white p-4 shadow-sm">
                            {qrCodeDataUrl ? (
                                <img
                                    src={qrCodeDataUrl}
                                    alt={`QR check-in ${member.name}`}
                                    className="size-full"
                                />
                            ) : (
                                <div className="flex h-full w-full flex-col items-center justify-center rounded-xl border border-dashed text-center text-sm text-muted-foreground">
                                    <QrCode className="mb-3 size-8" />
                                    QR belum tersedia
                                </div>
                            )}
                        </div>

                        <div>
                            <p className="text-xs font-medium tracking-[0.12em] text-muted-foreground uppercase">
                                Cara menggunakan
                            </p>
                            <h2 className="mt-2 text-xl font-semibold">
                                Tunjukkan QR ke Front Office
                            </h2>
                            <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                Petugas akan memindai QR ini. Sistem tetap
                                memeriksa membership dan kelayakan check-in
                                secara otomatis.
                            </p>
                            <div className="mt-5 flex items-start gap-3 rounded-xl bg-muted/40 p-4">
                                <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-emerald-600" />
                                <p className="text-xs leading-5 text-muted-foreground">
                                    QR bersifat pribadi. Jangan membagikan
                                    tangkapan layar kartu member kepada orang
                                    lain.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="rounded-2xl border bg-background p-5 sm:p-6">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                Membership saat ini
                            </p>
                            <h2 className="mt-2 text-xl font-semibold">
                                {membership?.plan_name ??
                                    'Belum ada membership'}
                            </h2>
                        </div>
                        {membership && (
                            <MemberMembershipStatusBadge
                                status={membership.status}
                                label={membership.status_label}
                            />
                        )}
                    </div>

                    {membership ? (
                        <>
                            <dl className="mt-6 grid gap-5 border-y py-5 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                                <InfoItem
                                    label="Mulai"
                                    value={formatDate(
                                        membership.start_date,
                                        timezone,
                                    )}
                                />
                                <InfoItem
                                    label="Berakhir"
                                    value={formatDate(
                                        membership.end_date,
                                        timezone,
                                    )}
                                />
                                <InfoItem
                                    label="Sisa masa aktif"
                                    value={
                                        membership.days_remaining !== null
                                            ? `${membership.days_remaining} hari`
                                            : membership.status_label
                                    }
                                    attention={membership.is_expiring_soon}
                                />
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Pembayaran
                                    </dt>
                                    <dd className="mt-1.5">
                                        {membership.payment_status &&
                                        membership.payment_status_label ? (
                                            <PaymentStatusBadge
                                                status={
                                                    membership.payment_status
                                                }
                                                label={
                                                    membership.payment_status_label
                                                }
                                            />
                                        ) : (
                                            <span className="text-sm font-medium">
                                                Belum ada invoice
                                            </span>
                                        )}
                                    </dd>
                                </div>
                            </dl>

                            {membership.is_expiring_soon && (
                                <p className="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                                    Membership segera berakhir. Hubungi Front
                                    Desk untuk perpanjangan.
                                </p>
                            )}
                        </>
                    ) : (
                        <div className="mt-6 rounded-xl border border-dashed bg-muted/20 px-5 py-8 text-center text-sm text-muted-foreground">
                            Hubungi Front Office untuk menetapkan membership
                            Anda.
                        </div>
                    )}
                </section>
            </div>

            <section className="grid gap-4 md:grid-cols-3">
                <SummaryCard
                    icon={<History />}
                    label="Check-in terakhir"
                    value={
                        latestCheckInAt
                            ? formatDateTime(latestCheckInAt, timezone)
                            : 'Belum ada'
                    }
                    detail="Lihat seluruh riwayat kunjungan"
                    href={MemberPortalController.checkIns().url}
                />
                <SummaryCard
                    icon={<CreditCard />}
                    label="Status pembayaran"
                    value={membership?.payment_status_label ?? 'Belum tersedia'}
                    detail="Buka invoice dan riwayat pembayaran"
                    href={MemberPortalController.payments().url}
                />
                <SummaryCard
                    icon={<Dumbbell />}
                    label="Sesi PT tersedia"
                    value={String(activePtPackage?.available_sessions ?? 0)}
                    detail={activePtPackage?.name ?? 'Belum ada paket aktif'}
                    href={MemberPortalController.personalTraining().url}
                />
            </section>
        </div>
    );
}

function InfoItem({
    label,
    value,
    attention = false,
}: {
    label: string;
    value: string;
    attention?: boolean;
}) {
    return (
        <div>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd
                className={cn(
                    'mt-1 text-sm font-semibold',
                    attention && 'text-amber-700 dark:text-amber-300',
                )}
            >
                {value}
            </dd>
        </div>
    );
}

function SummaryCard({
    icon,
    label,
    value,
    detail,
    href,
}: {
    icon: ReactNode;
    label: string;
    value: string;
    detail: string;
    href: string;
}) {
    return (
        <article className="flex flex-col rounded-xl border bg-background p-5">
            <span className="flex size-9 items-center justify-center rounded-lg bg-muted text-muted-foreground [&_svg]:size-4">
                {icon}
            </span>
            <p className="mt-4 text-xs font-medium text-muted-foreground">
                {label}
            </p>
            <p className="mt-1 text-lg font-semibold">{value}</p>
            <p className="mt-1 line-clamp-1 text-xs text-muted-foreground">
                {detail}
            </p>
            <Button
                variant="link"
                size="sm"
                className="mt-3 h-auto justify-start px-0"
                asChild
            >
                <Link href={href} prefetch>
                    Lihat detail <ArrowRight />
                </Link>
            </Button>
        </article>
    );
}
