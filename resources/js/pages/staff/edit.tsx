import { Head } from '@inertiajs/react';
import StaffController from '@/actions/App/Http/Controllers/StaffController';
import { FrontDeskForm } from '@/components/staff/front-desk-form';
import { index } from '@/routes/staff';
import type { SelectOption, StaffMember } from '@/types';

export default function EditStaff({
    staffMember,
    statusOptions,
}: {
    staffMember: StaffMember;
    statusOptions: SelectOption[];
}) {
    return (
        <>
            <Head title={`Edit ${staffMember.name}`} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">
                        {staffMember.name}
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold">
                        Edit Front Desk
                    </h1>
                </header>
                <FrontDeskForm
                    form={StaffController.update.form(staffMember.id)}
                    staffMember={staffMember}
                    statusOptions={statusOptions}
                    cancelHref={index()}
                    submitLabel="Simpan perubahan"
                />
            </div>
        </>
    );
}

EditStaff.layout = {
    breadcrumbs: [
        { title: 'Staf Gym', href: index() },
        { title: 'Edit Front Desk', href: '#' },
    ],
};
