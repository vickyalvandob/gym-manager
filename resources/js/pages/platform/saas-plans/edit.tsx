import { Head } from '@inertiajs/react';
import SaasPlanController from '@/actions/App/Http/Controllers/SaasPlanController';
import { SaasPlanForm } from '@/components/platform/saas-plan-form';
import { index } from '@/routes/platform/saas-plans';
import type { SaasPlan, SelectOption } from '@/types';

export default function EditSaasPlan({
    plan,
    intervalOptions,
}: {
    plan: SaasPlan;
    intervalOptions: SelectOption[];
}) {
    return (
        <>
            <Head title={`Edit ${plan.name}`} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Edit {plan.name}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Perubahan paket tidak menghapus histori subscription.
                    </p>
                </div>
                <SaasPlanForm
                    form={SaasPlanController.update.form.put(plan.id)}
                    plan={plan}
                    intervalOptions={intervalOptions}
                    cancelHref={index()}
                    submitLabel="Simpan perubahan"
                />
            </div>
        </>
    );
}

EditSaasPlan.layout = {
    breadcrumbs: [
        { title: 'Paket SaaS', href: index() },
        { title: 'Edit paket', href: '#' },
    ],
};
