import { Head } from '@inertiajs/react';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import { MemberForm } from '@/components/members/member-form';
import { index, show } from '@/routes/members';
import type { MemberDetail, SelectOption } from '@/types';

export default function EditMember({
    member,
    genderOptions,
    statusOptions,
}: {
    member: MemberDetail;
    genderOptions: SelectOption[];
    statusOptions: SelectOption[];
}) {
    return (
        <>
            <Head title={`Edit ${member.name}`} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">
                        {member.member_number}
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                        Edit member
                    </h1>
                </header>

                <MemberForm
                    form={MemberController.update.form.patch(member.id)}
                    member={member}
                    genderOptions={genderOptions}
                    statusOptions={statusOptions}
                    cancelHref={show(member.id)}
                    submitLabel="Simpan perubahan"
                />
            </div>
        </>
    );
}

EditMember.layout = {
    breadcrumbs: [
        { title: 'Member', href: index() },
        { title: 'Edit member', href: '#' },
    ],
};
