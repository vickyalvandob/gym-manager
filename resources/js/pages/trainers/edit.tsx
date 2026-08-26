import { Head } from '@inertiajs/react';
import TrainerController from '@/actions/App/Http/Controllers/TrainerController';
import { TrainerForm } from '@/components/trainers/trainer-form';
import { index, show } from '@/routes/trainers';
import type { SelectOption, Trainer } from '@/types';

export default function EditTrainer({
    trainer,
    statusOptions,
}: {
    trainer: Trainer;
    statusOptions: SelectOption[];
}) {
    return (
        <>
            <Head title={`Edit ${trainer.name}`} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">
                        {trainer.name}
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                        Edit trainer
                    </h1>
                </header>

                <TrainerForm
                    form={TrainerController.update.form.patch(trainer.id)}
                    trainer={trainer}
                    statusOptions={statusOptions}
                    cancelHref={show(trainer.id)}
                    submitLabel="Simpan perubahan"
                />
            </div>
        </>
    );
}

EditTrainer.layout = {
    breadcrumbs: [
        { title: 'Trainer', href: index() },
        { title: 'Edit trainer', href: '#' },
    ],
};
