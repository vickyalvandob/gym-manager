import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, CalendarClock, Pencil, ReceiptText } from 'lucide-react';
import MembershipPlanController from '@/actions/App/Http/Controllers/MembershipPlanController';
import { MembershipPlanDeleteDialog } from '@/components/membership-plans/membership-plan-delete-dialog';
import { MembershipPlanStatusBadge } from '@/components/membership-plans/membership-plan-status-badge';
import { MembershipPlanStatusDialog } from '@/components/membership-plans/membership-plan-status-dialog';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate } from '@/lib/formatters';
import { index } from '@/routes/membership-plans';
import type { MembershipPlan } from '@/types';

export default function ShowMembershipPlan({
    membershipPlan,
}: {
    membershipPlan: MembershipPlan;
}) {
    const { auth } = usePage().props;
    const currency = auth.currentGym?.currency ?? 'IDR';

    return (
        <>
            <Head title={membershipPlan.name} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
                    <div className="flex min-w-0 items-start gap-3">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="mt-1 shrink-0"
                            title="Kembali ke paket membership"
                            asChild
                        >
                            <Link href={index()}>
                                <ArrowLeft />
                                <span className="sr-only">
                                    Kembali ke paket membership
                                </span>
                            </Link>
                        </Button>
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <h1 className="truncate text-2xl font-semibold tracking-normal">
                                    {membershipPlan.name}
                                </h1>
                                <MembershipPlanStatusBadge
                                    isActive={membershipPlan.is_active}
                                    label={membershipPlan.status_label}
                                />
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {membershipPlan.duration_label}
                            </p>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2 pl-12 sm:pl-0">
                        <MembershipPlanStatusDialog
                            membershipPlanId={membershipPlan.id}
                            membershipPlanName={membershipPlan.name}
                            isActive={membershipPlan.is_active}
                        />
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={MembershipPlanController.edit(
                                    membershipPlan.id,
                                )}
                            >
                                <Pencil />
                                Edit
                            </Link>
                        </Button>
                        <MembershipPlanDeleteDialog
                            membershipPlanId={membershipPlan.id}
                            membershipPlanName={membershipPlan.name}
                        />
                    </div>
                </header>

                <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <main className="min-w-0 divide-y border-y">
                        <section className="py-6">
                            <h2 className="text-base font-semibold">
                                Rincian paket
                            </h2>
                            <dl className="mt-5 grid gap-6 sm:grid-cols-2">
                                <div className="flex gap-3">
                                    <CalendarClock className="mt-0.5 size-4 text-muted-foreground" />
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            Durasi
                                        </dt>
                                        <dd className="mt-1 text-sm font-medium">
                                            {membershipPlan.duration_label}
                                        </dd>
                                    </div>
                                </div>
                                <div className="flex gap-3">
                                    <ReceiptText className="mt-0.5 size-4 text-muted-foreground" />
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            Harga
                                        </dt>
                                        <dd className="mt-1 text-sm font-medium tabular-nums">
                                            {formatCurrency(
                                                membershipPlan.price,
                                                currency,
                                            )}
                                        </dd>
                                    </div>
                                </div>
                            </dl>
                        </section>

                        <section className="py-6">
                            <h2 className="text-base font-semibold">
                                Deskripsi
                            </h2>
                            <p className="mt-3 text-sm leading-6 whitespace-pre-wrap text-muted-foreground">
                                {membershipPlan.description ??
                                    'Tidak ada deskripsi.'}
                            </p>
                        </section>
                    </main>

                    <aside>
                        <h2 className="text-base font-semibold">
                            Riwayat data
                        </h2>
                        <dl className="mt-4 divide-y border-y text-sm">
                            <div className="py-3">
                                <dt className="text-muted-foreground">
                                    Dibuat
                                </dt>
                                <dd className="mt-1 font-medium">
                                    {formatDate(membershipPlan.created_at)}
                                </dd>
                            </div>
                            <div className="py-3">
                                <dt className="text-muted-foreground">
                                    Diperbarui
                                </dt>
                                <dd className="mt-1 font-medium">
                                    {formatDate(membershipPlan.updated_at)}
                                </dd>
                            </div>
                        </dl>
                    </aside>
                </div>
            </div>
        </>
    );
}

ShowMembershipPlan.layout = {
    breadcrumbs: [
        { title: 'Paket Membership', href: index() },
        { title: 'Detail paket', href: index() },
    ],
};
