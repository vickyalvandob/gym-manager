import { Head } from '@inertiajs/react';
import SaasPlanController from '@/actions/App/Http/Controllers/SaasPlanController';
import { SaasPlanForm } from '@/components/platform/saas-plan-form';
import { index } from '@/routes/platform/saas-plans';
import type { SelectOption } from '@/types';

export default function CreateSaasPlan({
    intervalOptions,
}: {
    intervalOptions: SelectOption[];
}) {
    return (
        <>
            <Head title="Tambah Paket SaaS" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Tambah paket SaaS
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Paket aktif akan langsung tersedia di halaman
                        pendaftaran.
                    </p>
                </div>
                <SaasPlanForm
                    form={SaasPlanController.store.form()}
                    intervalOptions={intervalOptions}
                    cancelHref={index()}
                    submitLabel="Simpan paket"
                />
            </div>
        </>
    );
}

CreateSaasPlan.layout = {
    breadcrumbs: [
        { title: 'Paket SaaS', href: index() },
        { title: 'Tambah', href: SaasPlanController.create() },
    ],
};
