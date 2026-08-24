import { Head } from '@inertiajs/react';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import { MemberForm } from '@/components/members/member-form';
import { index } from '@/routes/members';
import type { SelectOption } from '@/types';

export default function CreateMember({
    genderOptions,
    statusOptions,
}: {
    genderOptions: SelectOption[];
    statusOptions: SelectOption[];
}) {
    return (
        <>
            <Head title="Tambah Member" />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">Member</p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                        Tambah member
                    </h1>
                </header>

                <MemberForm
                    form={MemberController.store.form()}
                    genderOptions={genderOptions}
                    statusOptions={statusOptions}
                    cancelHref={index()}
                    submitLabel="Simpan member"
                />
            </div>
        </>
    );
}

CreateMember.layout = {
    breadcrumbs: [
        { title: 'Member', href: index() },
        { title: 'Tambah member', href: MemberController.create() },
    ],
};
