import { Head } from '@inertiajs/react';
import { PortalPageHeader } from '@/components/member-portal/portal-page-header';
import { PortalPagination } from '@/components/member-portal/portal-pagination';
import { MemberMembershipStatusBadge } from '@/components/members/member-membership-status-badge';
import { PaymentStatusBadge } from '@/components/payments/payment-status-badge';
import { formatDate } from '@/lib/formatters';
import type {
    MemberPortalBaseProps,
    MemberPortalMembership,
    MemberPortalPaginator,
} from '@/types';

export default function MemberMembership({
    member,
    memberships,
}: MemberPortalBaseProps & {
    memberships: MemberPortalPaginator<MemberPortalMembership>;
}) {
    return (
        <div className="mx-auto max-w-4xl space-y-6">
            <Head title="Membership" />
            <PortalPageHeader
                eyebrow="My Gymlo"
                title="Membership"
                description="Paket, masa berlaku, dan riwayat perpanjangan Anda. Hubungi Front Office untuk memperpanjang."
            />
            <section className="overflow-hidden rounded-xl border bg-background">
                {memberships.data.length === 0 ? (
                    <p className="p-6 text-sm text-muted-foreground">
                        Belum ada membership.
                    </p>
                ) : (
                    memberships.data.map((membership) => (
                        <article
                            key={membership.id}
                            className="space-y-4 border-b p-5 last:border-0 sm:p-6"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <h2 className="font-semibold">
                                    {membership.plan_name}
                                </h2>
                                <MemberMembershipStatusBadge
                                    status={membership.status}
                                    label={membership.status_label}
                                />
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {formatDate(
                                    membership.start_date,
                                    member.gym.timezone,
                                )}{' '}
                                –{' '}
                                {formatDate(
                                    membership.end_date,
                                    member.gym.timezone,
                                )}
                            </p>
                            {membership.days_remaining !== null && (
                                <p className="text-sm">
                                    Sisa {membership.days_remaining} hari
                                </p>
                            )}
                            {membership.payment_status && (
                                <PaymentStatusBadge
                                    status={membership.payment_status}
                                    label={
                                        membership.payment_status_label ?? ''
                                    }
                                />
                            )}
                        </article>
                    ))
                )}
                <PortalPagination paginator={memberships} />
            </section>
        </div>
    );
}
