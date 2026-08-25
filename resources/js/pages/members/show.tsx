import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Mail,
    MapPin,
    Pencil,
    Phone,
    ShieldAlert,
    UserRound,
} from 'lucide-react';
import type { ReactNode } from 'react';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import { CheckInButton } from '@/components/check-ins/check-in-button';
import { MemberAvatar } from '@/components/members/member-avatar';
import { MemberMembershipDialog } from '@/components/members/member-membership-dialog';
import { MemberMembershipStatusBadge } from '@/components/members/member-membership-status-badge';
import { MemberStatusBadge } from '@/components/members/member-status-badge';
import { MemberStatusDialog } from '@/components/members/member-status-dialog';
import { CreateMembershipPaymentButton } from '@/components/payments/create-membership-payment-button';
import { PaymentStatusBadge } from '@/components/payments/payment-status-badge';
import { RecordPaymentDialog } from '@/components/payments/record-payment-dialog';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate, formatDateTime } from '@/lib/formatters';
import { index } from '@/routes/members';
import type {
    CheckInEligibility,
    MemberDetail,
    MemberMembership,
    MembershipDefaults,
    MembershipPlanOption,
    PaginatedMemberMemberships,
    PaginatedCheckIns,
    SelectOption,
} from '@/types';

type ShowMemberProps = {
    member: MemberDetail;
    activeMembership: MemberMembership | null;
    upcomingMembership: MemberMembership | null;
    memberships: PaginatedMemberMemberships;
    checkIns: PaginatedCheckIns;
    checkInEligibility: CheckInEligibility;
    membershipPlans: MembershipPlanOption[];
    paymentMethodOptions: SelectOption[];
    membershipDefaults: MembershipDefaults;
};

export default function ShowMember({
    member,
    activeMembership,
    upcomingMembership,
    memberships,
    checkIns,
    checkInEligibility,
    membershipPlans,
    paymentMethodOptions,
    membershipDefaults,
}: ShowMemberProps) {
    const { auth } = usePage().props;
    const currency = auth.currentGym?.currency ?? 'IDR';
    const hasMembershipHistory = memberships.total > 0;

    return (
        <>
            <Head title={member.name} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
                    <div className="flex min-w-0 items-start gap-4">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="mt-1 shrink-0"
                            title="Kembali ke member"
                            asChild
                        >
                            <Link href={index()}>
                                <ArrowLeft />
                                <span className="sr-only">
                                    Kembali ke member
                                </span>
                            </Link>
                        </Button>
                        <MemberAvatar
                            name={member.name}
                            photoUrl={member.photo_url}
                            className="size-14 rounded-lg"
                        />
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <h1 className="truncate text-2xl font-semibold tracking-normal">
                                    {member.name}
                                </h1>
                                <MemberStatusBadge
                                    status={member.status}
                                    label={member.status_label}
                                />
                            </div>
                            <p className="mt-1 font-mono text-sm text-muted-foreground">
                                {member.member_number}
                            </p>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2 pl-14 sm:pl-0">
                        <CheckInButton
                            memberId={member.id}
                            disabled={!checkInEligibility.can_check_in}
                            disabledReason={checkInEligibility.reason}
                        />
                        {hasMembershipHistory ? (
                            <MemberMembershipDialog
                                memberId={member.id}
                                memberName={member.name}
                                currency={currency}
                                membershipPlans={membershipPlans}
                                defaultPlanId={
                                    membershipDefaults.renewal_plan_id
                                }
                                defaultStartDate={
                                    membershipDefaults.renewal_start_date
                                }
                                mode="renew"
                                renewalSourceId={
                                    membershipDefaults.renewal_source_id
                                }
                            />
                        ) : (
                            <MemberMembershipDialog
                                memberId={member.id}
                                memberName={member.name}
                                currency={currency}
                                membershipPlans={membershipPlans}
                                defaultPlanId={membershipPlans[0]?.id ?? null}
                                defaultStartDate={
                                    membershipDefaults.assign_start_date
                                }
                                mode="assign"
                            />
                        )}
                        <MemberStatusDialog
                            memberId={member.id}
                            memberName={member.name}
                            status={member.status}
                        />
                        <Button variant="outline" size="sm" asChild>
                            <Link href={MemberController.edit(member.id)}>
                                <Pencil />
                                Edit
                            </Link>
                        </Button>
                    </div>
                </header>

                <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_19rem]">
                    <main className="min-w-0 divide-y border-y">
                        <section className="py-6">
                            <h2 className="text-base font-semibold">Profil</h2>
                            <dl className="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                                <DetailItem
                                    icon={<Phone />}
                                    label="Nomor telepon"
                                    value={member.phone}
                                    href={`tel:${member.phone}`}
                                />
                                <DetailItem
                                    icon={<Mail />}
                                    label="Email"
                                    value={member.email ?? '-'}
                                    href={
                                        member.email
                                            ? `mailto:${member.email}`
                                            : undefined
                                    }
                                />
                                <DetailItem
                                    icon={<UserRound />}
                                    label="Jenis kelamin"
                                    value={member.gender_label ?? '-'}
                                />
                                <DetailItem
                                    icon={<CalendarDays />}
                                    label="Tanggal lahir"
                                    value={formatDate(member.birth_date)}
                                />
                                <DetailItem
                                    icon={<ShieldAlert />}
                                    label="Kontak darurat"
                                    value={member.emergency_contact ?? '-'}
                                />
                                <DetailItem
                                    icon={<MapPin />}
                                    label="Alamat"
                                    value={member.address ?? '-'}
                                />
                            </dl>
                        </section>

                        <section className="py-6">
                            <h2 className="text-base font-semibold">Catatan</h2>
                            <p className="mt-3 text-sm leading-6 whitespace-pre-wrap text-muted-foreground">
                                {member.notes ?? 'Tidak ada catatan.'}
                            </p>
                        </section>

                        <MembershipHistory
                            memberships={memberships}
                            currency={currency}
                            methodOptions={paymentMethodOptions}
                        />
                        <CheckInHistory
                            checkIns={checkIns}
                            timezone={
                                auth.currentGym?.timezone ?? 'Asia/Jakarta'
                            }
                        />
                    </main>

                    <aside className="space-y-7">
                        <section>
                            <div className="flex items-center justify-between gap-3">
                                <h2 className="text-base font-semibold">
                                    Keanggotaan
                                </h2>
                                {activeMembership && (
                                    <MemberMembershipStatusBadge
                                        status={activeMembership.status}
                                        label={activeMembership.status_label}
                                        isExpiringSoon={
                                            activeMembership.is_expiring_soon
                                        }
                                    />
                                )}
                            </div>

                            {activeMembership ? (
                                <MembershipSummary
                                    membership={activeMembership}
                                    currency={currency}
                                    methodOptions={paymentMethodOptions}
                                />
                            ) : (
                                <div className="mt-4 border-y py-4">
                                    <p className="text-sm font-medium">
                                        Tidak ada paket aktif
                                    </p>
                                    <p className="mt-1 text-xs leading-5 text-muted-foreground">
                                        {hasMembershipHistory
                                            ? 'Membership terakhir sudah kedaluwarsa.'
                                            : 'Tetapkan paket untuk memulai membership.'}
                                    </p>
                                </div>
                            )}

                            {upcomingMembership && (
                                <div className="mt-4 border-t pt-4">
                                    <div className="flex items-center justify-between gap-2">
                                        <p className="text-xs font-medium text-muted-foreground">
                                            Periode berikutnya
                                        </p>
                                        <MemberMembershipStatusBadge
                                            status={upcomingMembership.status}
                                            label={
                                                upcomingMembership.status_label
                                            }
                                        />
                                    </div>
                                    <p className="mt-2 text-sm font-medium">
                                        {upcomingMembership.plan_name}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Mulai{' '}
                                        {formatDate(
                                            upcomingMembership.start_date,
                                        )}
                                    </p>
                                </div>
                            )}

                            {membershipPlans.length === 0 && (
                                <p className="mt-3 text-xs leading-5 text-amber-700 dark:text-amber-300">
                                    Aktifkan minimal satu paket membership untuk
                                    assignment atau renewal.
                                </p>
                            )}

                            {!checkInEligibility.can_check_in &&
                                checkInEligibility.reason && (
                                    <p className="mt-3 text-xs leading-5 text-muted-foreground">
                                        {checkInEligibility.reason}
                                    </p>
                                )}
                        </section>

                        <section>
                            <h2 className="text-base font-semibold">
                                Riwayat data
                            </h2>
                            <dl className="mt-4 divide-y border-y text-sm">
                                <div className="py-3">
                                    <dt className="text-muted-foreground">
                                        Terdaftar
                                    </dt>
                                    <dd className="mt-1 font-medium">
                                        {formatDate(member.created_at)}
                                    </dd>
                                </div>
                                <div className="py-3">
                                    <dt className="text-muted-foreground">
                                        Diperbarui
                                    </dt>
                                    <dd className="mt-1 font-medium">
                                        {formatDate(member.updated_at)}
                                    </dd>
                                </div>
                            </dl>
                        </section>
                    </aside>
                </div>
            </div>
        </>
    );
}

function MembershipSummary({
    membership,
    currency,
    methodOptions,
}: {
    membership: MemberMembership;
    currency: string;
    methodOptions: SelectOption[];
}) {
    return (
        <dl className="mt-4 divide-y border-y text-sm">
            <div className="py-3">
                <dt className="text-muted-foreground">Paket</dt>
                <dd className="mt-1 font-medium">{membership.plan_name}</dd>
            </div>
            <div className="grid grid-cols-2 gap-3 py-3">
                <div>
                    <dt className="text-muted-foreground">Mulai</dt>
                    <dd className="mt-1 font-medium">
                        {formatDate(membership.start_date)}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">Berakhir</dt>
                    <dd className="mt-1 font-medium">
                        {formatDate(membership.end_date)}
                    </dd>
                </div>
            </div>
            <div className="py-3">
                <dt className="text-muted-foreground">Nilai paket</dt>
                <dd className="mt-1 font-medium tabular-nums">
                    {formatCurrency(membership.price, currency)}
                </dd>
                {membership.is_expiring_soon && (
                    <p className="mt-2 text-xs font-medium text-amber-700 dark:text-amber-300">
                        {membership.days_remaining === 0
                            ? 'Berakhir hari ini'
                            : `Berakhir dalam ${membership.days_remaining} hari`}
                    </p>
                )}
            </div>
            <div className="py-3">
                <dt className="text-muted-foreground">Pembayaran</dt>
                <dd className="mt-2">
                    <MembershipPaymentInfo
                        membership={membership}
                        currency={currency}
                        methodOptions={methodOptions}
                    />
                </dd>
            </div>
        </dl>
    );
}

function MembershipHistory({
    memberships,
    currency,
    methodOptions,
}: {
    memberships: PaginatedMemberMemberships;
    currency: string;
    methodOptions: SelectOption[];
}) {
    return (
        <section className="py-6">
            <div>
                <h2 className="text-base font-semibold">Riwayat membership</h2>
                <p className="mt-1 text-xs text-muted-foreground">
                    {memberships.total} periode tersimpan
                </p>
            </div>

            {memberships.data.length === 0 ? (
                <p className="mt-5 text-sm text-muted-foreground">
                    Belum ada riwayat membership.
                </p>
            ) : (
                <>
                    <div className="mt-5 hidden overflow-hidden rounded-lg border md:block">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/50 text-xs text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Paket
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Periode
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Nilai paket
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Pembayaran
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {memberships.data.map((membership) => (
                                    <tr key={membership.id}>
                                        <td className="px-4 py-3">
                                            <p className="font-medium">
                                                {membership.plan_name}
                                            </p>
                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                {membership.duration_label}
                                                {membership.renewed_from_id
                                                    ? ' · Renewal'
                                                    : ''}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            <p>
                                                {formatDate(
                                                    membership.start_date,
                                                )}
                                            </p>
                                            <p className="mt-0.5 text-muted-foreground">
                                                s.d.{' '}
                                                {formatDate(
                                                    membership.end_date,
                                                )}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3">
                                            <MemberMembershipStatusBadge
                                                status={membership.status}
                                                label={membership.status_label}
                                                isExpiringSoon={
                                                    membership.is_expiring_soon
                                                }
                                            />
                                        </td>
                                        <td className="px-4 py-3 text-right font-medium tabular-nums">
                                            {formatCurrency(
                                                membership.price,
                                                currency,
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <MembershipPaymentInfo
                                                membership={membership}
                                                currency={currency}
                                                methodOptions={methodOptions}
                                                compact
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-5 divide-y border-y md:hidden">
                        {memberships.data.map((membership) => (
                            <article key={membership.id} className="py-4">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-medium">
                                            {membership.plan_name}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {formatDate(membership.start_date)}{' '}
                                            · {formatDate(membership.end_date)}
                                        </p>
                                    </div>
                                    <MemberMembershipStatusBadge
                                        status={membership.status}
                                        label={membership.status_label}
                                        isExpiringSoon={
                                            membership.is_expiring_soon
                                        }
                                    />
                                </div>
                                <p className="mt-3 text-sm font-medium tabular-nums">
                                    {formatCurrency(membership.price, currency)}
                                </p>
                                <div className="mt-3">
                                    <MembershipPaymentInfo
                                        membership={membership}
                                        currency={currency}
                                        methodOptions={methodOptions}
                                    />
                                </div>
                            </article>
                        ))}
                    </div>
                </>
            )}

            {memberships.last_page > 1 && (
                <nav
                    className="mt-4 flex items-center justify-between gap-3"
                    aria-label="Pagination riwayat membership"
                >
                    <p className="hidden text-xs text-muted-foreground sm:block">
                        {memberships.from}-{memberships.to} dari{' '}
                        {memberships.total}
                    </p>
                    <div className="flex w-full justify-between gap-2 sm:w-auto">
                        <MembershipPaginationButton
                            url={memberships.links[0]?.url ?? null}
                            label="Sebelumnya"
                            icon={<ChevronLeft />}
                        />
                        <MembershipPaginationButton
                            url={memberships.links.at(-1)?.url ?? null}
                            label="Berikutnya"
                            icon={<ChevronRight />}
                            iconAfter
                        />
                    </div>
                </nav>
            )}
        </section>
    );
}

function CheckInHistory({
    checkIns,
    timezone,
}: {
    checkIns: PaginatedCheckIns;
    timezone: string;
}) {
    return (
        <section className="py-6">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <h2 className="text-base font-semibold">
                        Riwayat check-in
                    </h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {checkIns.total} kunjungan tersimpan
                    </p>
                </div>
                <Clock3 className="size-5 text-muted-foreground" />
            </div>

            {checkIns.data.length === 0 ? (
                <p className="mt-5 text-sm text-muted-foreground">
                    Belum ada riwayat check-in.
                </p>
            ) : (
                <>
                    <div className="mt-5 hidden overflow-hidden rounded-lg border sm:block">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/50 text-xs text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Waktu
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Membership
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Petugas
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {checkIns.data.map((checkIn) => (
                                    <tr key={checkIn.id}>
                                        <td className="px-4 py-3 text-xs tabular-nums">
                                            {formatDateTime(
                                                checkIn.checked_in_at,
                                                timezone,
                                            )}
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
                                            {checkIn.created_by?.name ??
                                                'Akun dihapus'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-5 divide-y border-y sm:hidden">
                        {checkIns.data.map((checkIn) => (
                            <article key={checkIn.id} className="py-4">
                                <p className="text-sm font-medium">
                                    {checkIn.membership.plan_name}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {formatDateTime(
                                        checkIn.checked_in_at,
                                        timezone,
                                    )}{' '}
                                    ·{' '}
                                    {checkIn.created_by?.name ?? 'Akun dihapus'}
                                </p>
                            </article>
                        ))}
                    </div>
                </>
            )}

            {checkIns.last_page > 1 && (
                <nav
                    className="mt-4 flex items-center justify-between gap-3"
                    aria-label="Pagination riwayat check-in member"
                >
                    <p className="hidden text-xs text-muted-foreground sm:block">
                        {checkIns.from}-{checkIns.to} dari {checkIns.total}
                    </p>
                    <div className="flex w-full justify-between gap-2 sm:w-auto">
                        <MembershipPaginationButton
                            url={checkIns.links[0]?.url ?? null}
                            label="Sebelumnya"
                            icon={<ChevronLeft />}
                        />
                        <MembershipPaginationButton
                            url={checkIns.links.at(-1)?.url ?? null}
                            label="Berikutnya"
                            icon={<ChevronRight />}
                            iconAfter
                        />
                    </div>
                </nav>
            )}
        </section>
    );
}

function MembershipPaymentInfo({
    membership,
    currency,
    methodOptions,
    compact = false,
}: {
    membership: MemberMembership;
    currency: string;
    methodOptions: SelectOption[];
    compact?: boolean;
}) {
    const payment = membership.payment;

    if (!payment) {
        return (
            <CreateMembershipPaymentButton
                membershipId={membership.id}
                compact={compact}
            />
        );
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            <div className="min-w-0">
                <p className="truncate font-mono text-xs font-medium">
                    {payment.invoice_number}
                </p>
                <div className="mt-1 flex flex-wrap items-center gap-2">
                    <PaymentStatusBadge
                        status={payment.status}
                        label={payment.status_label}
                    />
                    {payment.method_label && (
                        <span className="text-xs text-muted-foreground">
                            {payment.method_label}
                        </span>
                    )}
                </div>
            </div>
            {payment.status === 'pending' && (
                <RecordPaymentDialog
                    payment={payment}
                    currency={currency}
                    methodOptions={methodOptions}
                    compact={compact}
                />
            )}
        </div>
    );
}

function MembershipPaginationButton({
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

function DetailItem({
    icon,
    label,
    value,
    href,
}: {
    icon: ReactNode;
    label: string;
    value: string;
    href?: string;
}) {
    const content = href ? (
        <a href={href} className="font-medium break-words hover:text-primary">
            {value}
        </a>
    ) : (
        <span className="font-medium break-words">{value}</span>
    );

    return (
        <div className="flex gap-3">
            <span className="mt-0.5 text-muted-foreground [&_svg]:size-4">
                {icon}
            </span>
            <div className="min-w-0">
                <dt className="text-xs text-muted-foreground">{label}</dt>
                <dd className="mt-1 text-sm">{content}</dd>
            </div>
        </div>
    );
}

ShowMember.layout = {
    breadcrumbs: [
        { title: 'Member', href: index() },
        { title: 'Detail member', href: index() },
    ],
};
