import { Head } from '@inertiajs/react';
import StaffController from '@/actions/App/Http/Controllers/StaffController';
import { FrontDeskForm } from '@/components/staff/front-desk-form';
import { index } from '@/routes/staff';
import type { SelectOption } from '@/types';

export default function CreateStaff({
    statusOptions,
}: {
    statusOptions: SelectOption[];
}) {
    return (
        <>
            <Head title="Tambah Front Desk" />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">Staf gym</p>
                    <h1 className="mt-1 text-2xl font-semibold">
                        Tambah Front Desk
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Buat akun operasional untuk gym yang sedang dipilih.
                    </p>
                </header>
                <FrontDeskForm
                    form={StaffController.store.form()}
                    statusOptions={statusOptions}
                    cancelHref={index()}
                    submitLabel="Simpan Front Desk"
                />
            </div>
        </>
    );
}

CreateStaff.layout = {
    breadcrumbs: [
        { title: 'Staf Gym', href: index() },
        { title: 'Tambah Front Desk', href: StaffController.create() },
    ],
};
