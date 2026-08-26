import { Head } from '@inertiajs/react';
import TrainerController from '@/actions/App/Http/Controllers/TrainerController';
import { TrainerForm } from '@/components/trainers/trainer-form';
import { index } from '@/routes/trainers';
import type { SelectOption, TrainerAccountOption } from '@/types';

export default function CreateTrainer({
    statusOptions,
    accountOptions,
}: {
    statusOptions: SelectOption[];
    accountOptions: TrainerAccountOption[];
}) {
    return (
        <>
            <Head title="Tambah Trainer" />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">Trainer</p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                        Tambah trainer
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Tambahkan profil trainer untuk mulai mengatur member
                        assignment.
                    </p>
                </header>

                <TrainerForm
                    form={TrainerController.store.form()}
                    statusOptions={statusOptions}
                    accountOptions={accountOptions}
                    cancelHref={index()}
                    submitLabel="Simpan trainer"
                />
            </div>
        </>
    );
}

CreateTrainer.layout = {
    breadcrumbs: [
        { title: 'Trainer', href: index() },
        { title: 'Tambah trainer', href: TrainerController.create() },
    ],
};
