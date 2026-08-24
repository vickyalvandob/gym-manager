import { Head } from '@inertiajs/react';
import MembershipPlanController from '@/actions/App/Http/Controllers/MembershipPlanController';
import { MembershipPlanForm } from '@/components/membership-plans/membership-plan-form';
import { index, show } from '@/routes/membership-plans';
import type { MembershipPlan, SelectOption } from '@/types';

export default function EditMembershipPlan({
    membershipPlan,
    durationOptions,
}: {
    membershipPlan: MembershipPlan;
    durationOptions: SelectOption[];
}) {
    return (
        <>
            <Head title={`Edit ${membershipPlan.name}`} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">
                        {membershipPlan.name}
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                        Edit paket
                    </h1>
                </header>

                <MembershipPlanForm
                    form={MembershipPlanController.update.form.patch(
                        membershipPlan.id,
                    )}
                    membershipPlan={membershipPlan}
                    durationOptions={durationOptions}
                    cancelHref={show(membershipPlan.id)}
                    submitLabel="Simpan perubahan"
                />
            </div>
        </>
    );
}

EditMembershipPlan.layout = {
    breadcrumbs: [
        { title: 'Paket Membership', href: index() },
        { title: 'Edit paket', href: '#' },
    ],
};
