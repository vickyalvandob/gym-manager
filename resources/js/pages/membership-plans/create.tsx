import { Head } from '@inertiajs/react';
import MembershipPlanController from '@/actions/App/Http/Controllers/MembershipPlanController';
import { MembershipPlanForm } from '@/components/membership-plans/membership-plan-form';
import { index } from '@/routes/membership-plans';
import type { SelectOption } from '@/types';

export default function CreateMembershipPlan({
    durationOptions,
}: {
    durationOptions: SelectOption[];
}) {
    return (
        <>
            <Head title="Tambah Paket Membership" />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">
                        Paket Membership
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                        Tambah paket
                    </h1>
                </header>

                <MembershipPlanForm
                    form={MembershipPlanController.store.form()}
                    durationOptions={durationOptions}
                    cancelHref={index()}
                    submitLabel="Simpan paket"
                />
            </div>
        </>
    );
}

CreateMembershipPlan.layout = {
    breadcrumbs: [
        { title: 'Paket Membership', href: index() },
        { title: 'Tambah paket', href: MembershipPlanController.create() },
    ],
};
