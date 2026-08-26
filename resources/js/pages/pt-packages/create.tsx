import { Head } from '@inertiajs/react';
import PtPackageController from '@/actions/App/Http/Controllers/PtPackageController';
import { PtPackageForm } from '@/components/personal-training/pt-package-form';
import { index } from '@/routes/pt-packages';

export default function CreatePtPackage() {
    return (
        <>
            <Head title="Tambah Paket PT" />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">
                        Personal Training
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold">
                        Tambah paket PT
                    </h1>
                </header>
                <PtPackageForm
                    form={PtPackageController.store.form()}
                    cancelHref={index()}
                    submitLabel="Simpan paket"
                />
            </div>
        </>
    );
}

CreatePtPackage.layout = {
    breadcrumbs: [
        { title: 'Paket PT', href: index() },
        { title: 'Tambah paket', href: PtPackageController.create() },
    ],
};
