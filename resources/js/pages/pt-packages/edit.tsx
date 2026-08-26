import { Head } from '@inertiajs/react';
import PtPackageController from '@/actions/App/Http/Controllers/PtPackageController';
import { PtPackageForm } from '@/components/personal-training/pt-package-form';
import { index, show } from '@/routes/pt-packages';
import type { PtPackage } from '@/types';

export default function EditPtPackage({ ptPackage }: { ptPackage: PtPackage }) {
    return (
        <>
            <Head title={`Edit ${ptPackage.name}`} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">
                        {ptPackage.name}
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold">
                        Edit paket PT
                    </h1>
                </header>
                <PtPackageForm
                    form={PtPackageController.update.form.patch(ptPackage.id)}
                    ptPackage={ptPackage}
                    cancelHref={show(ptPackage.id)}
                    submitLabel="Simpan perubahan"
                />
            </div>
        </>
    );
}

EditPtPackage.layout = {
    breadcrumbs: [
        { title: 'Paket PT', href: index() },
        { title: 'Edit paket', href: '#' },
    ],
};
