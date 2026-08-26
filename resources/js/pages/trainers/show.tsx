import { Form, Head, Link } from '@inertiajs/react';
import {
    CalendarClock,
    ChevronLeft,
    ChevronRight,
    KeyRound,
    Mail,
    Pencil,
    Phone,
    Plus,
    Search,
    UserRound,
    UsersRound,
} from 'lucide-react';
import type { ReactNode } from 'react';
import AssignTrainerMemberController from '@/actions/App/Http/Controllers/AssignTrainerMemberController';
import TrainerController from '@/actions/App/Http/Controllers/TrainerController';
import { MemberStatusBadge } from '@/components/members/member-status-badge';
import { TrainerStatusBadge } from '@/components/trainers/trainer-status-badge';
import { TrainerUnassignDialog } from '@/components/trainers/trainer-unassign-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate, formatDateTime } from '@/lib/formatters';
import { index } from '@/routes/trainers';
import type { PaginatedTrainerMembers, Trainer, TrainerMember } from '@/types';

export default function ShowTrainer({
    trainer,
    assignedMembers,
    memberSearch,
    assignableMembers,
    canEdit,
    canAssign,
}: {
    trainer: Trainer;
    assignedMembers: PaginatedTrainerMembers;
    memberSearch: string;
    assignableMembers: TrainerMember[];
    canEdit: boolean;
    canAssign: boolean;
}) {
    return (
        <>
            <Head title={trainer.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-7 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <p className="text-sm font-medium text-primary">
                                Trainer
                            </p>
                            <TrainerStatusBadge
                                status={trainer.status}
                                label={trainer.status_label}
                            />
                        </div>
                        <h1 className="mt-2 text-2xl font-semibold tracking-normal">
                            {trainer.name}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {trainer.trainer_code ??
                                'Kode trainer belum tersedia'}{' '}
                            ·{' '}
                            {trainer.specialization ??
                                'Spesialisasi belum ditentukan'}
                        </p>
                    </div>
                    {canEdit && (
                        <div className="flex gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={TrainerController.edit(trainer.id)}>
                                    <Pencil />
                                    Edit
                                </Link>
                            </Button>
                        </div>
                    )}
                </header>

                <section className="grid gap-px overflow-hidden rounded-lg border bg-border sm:grid-cols-2 xl:grid-cols-4">
                    <InfoItem
                        icon={<Phone />}
                        label="Telepon"
                        value={trainer.phone}
                    />
                    <InfoItem
                        icon={<Mail />}
                        label="Email"
                        value={trainer.email ?? 'Belum diisi'}
                    />
                    <InfoItem
                        icon={<UsersRound />}
                        label="Member assignment"
                        value={`${trainer.members_count} member`}
                    />
                    <InfoItem
                        icon={<KeyRound />}
                        label="Akun login"
                        value={trainer.linked_user?.email ?? 'Belum terhubung'}
                    />
                </section>

                {(trainer.bio || trainer.notes || trainer.linked_user) && (
                    <section className="grid gap-5 border-y py-5 lg:grid-cols-2">
                        <div>
                            <h2 className="text-sm font-semibold">
                                Profil profesional
                            </h2>
                            <p className="mt-2 text-sm leading-6 whitespace-pre-line text-muted-foreground">
                                {trainer.bio ?? 'Bio profesional belum diisi.'}
                            </p>
                            {trainer.joined_at && (
                                <p className="mt-3 text-xs text-muted-foreground">
                                    Bergabung {formatDate(trainer.joined_at)}
                                </p>
                            )}
                        </div>
                        {trainer.linked_user && (
                            <div>
                                <h2 className="text-sm font-semibold">
                                    Identitas akun
                                </h2>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    {trainer.linked_user.name}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Assignment trainer ini tampil pada dashboard
                                    akun tersebut.
                                </p>
                            </div>
                        )}
                        {trainer.notes && (
                            <div>
                                <h2 className="text-sm font-semibold">
                                    Catatan internal
                                </h2>
                                <p className="mt-2 text-sm leading-6 whitespace-pre-line text-muted-foreground">
                                    {trainer.notes}
                                </p>
                            </div>
                        )}
                    </section>
                )}

                <section
                    className="grid gap-5"
                    aria-labelledby="assignment-heading"
                >
                    <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                        <div>
                            <h2
                                id="assignment-heading"
                                className="text-base font-semibold"
                            >
                                Member assignment
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Member yang menjadi tanggung jawab trainer ini.
                            </p>
                        </div>
                        <p className="text-sm text-muted-foreground tabular-nums">
                            {assignedMembers.total} member
                        </p>
                    </div>

                    {canAssign && (
                        <div className="border-y py-5">
                            <Form
                                {...TrainerController.show.form(trainer.id)}
                                options={{
                                    preserveState: true,
                                    preserveScroll: true,
                                    only: ['assignableMembers', 'memberSearch'],
                                }}
                                className="grid gap-2"
                            >
                                {({ processing }) => (
                                    <>
                                        <label
                                            htmlFor="member_search"
                                            className="text-sm font-medium"
                                        >
                                            Tambahkan member aktif
                                        </label>
                                        <div className="flex gap-2">
                                            <div className="relative min-w-0 flex-1">
                                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                                <Input
                                                    id="member_search"
                                                    name="member_search"
                                                    defaultValue={memberSearch}
                                                    className="pl-9"
                                                    placeholder="Nama, nomor member, atau telepon"
                                                />
                                            </div>
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                disabled={processing}
                                            >
                                                <Search />
                                                Cari
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>

                            <div className="mt-4">
                                <p className="text-xs font-medium text-muted-foreground">
                                    {memberSearch
                                        ? `${assignableMembers.length} hasil pencarian`
                                        : 'Member aktif terbaru'}
                                </p>
                                {assignableMembers.length === 0 ? (
                                    <p className="mt-3 rounded-md bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                                        Tidak ada member aktif yang cocok atau
                                        semua hasil sudah ditugaskan.
                                    </p>
                                ) : (
                                    <div className="mt-3 grid gap-2 sm:grid-cols-2">
                                        {assignableMembers.map((member) => (
                                            <Form
                                                key={member.id}
                                                {...AssignTrainerMemberController.form(
                                                    trainer.id,
                                                )}
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                                className="flex items-center justify-between gap-3 rounded-md border p-3"
                                            >
                                                {({ processing }) => (
                                                    <>
                                                        <input
                                                            type="hidden"
                                                            name="member_id"
                                                            value={member.id}
                                                        />
                                                        <div className="min-w-0">
                                                            <p className="truncate text-sm font-medium">
                                                                {member.name}
                                                            </p>
                                                            <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                                                {
                                                                    member.member_number
                                                                }{' '}
                                                                · {member.phone}
                                                            </p>
                                                        </div>
                                                        <Button
                                                            type="submit"
                                                            variant="outline"
                                                            size="sm"
                                                            disabled={
                                                                processing
                                                            }
                                                            data-test={`assign-trainer-member-${member.id}`}
                                                        >
                                                            <Plus />
                                                            {processing
                                                                ? 'Menugaskan...'
                                                                : 'Tugaskan'}
                                                        </Button>
                                                    </>
                                                )}
                                            </Form>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {assignedMembers.data.length === 0 ? (
                        <div className="flex min-h-56 flex-col items-center justify-center border-y px-6 py-10 text-center">
                            <UserRound className="size-8 text-muted-foreground" />
                            <h3 className="mt-3 text-sm font-semibold">
                                Belum ada member assignment
                            </h3>
                            <p className="mt-1 max-w-md text-sm leading-6 text-muted-foreground">
                                {canAssign
                                    ? 'Cari member aktif di atas, lalu tugaskan ke trainer ini.'
                                    : 'Owner atau Front Desk belum menambahkan member untuk trainer ini.'}
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="hidden overflow-hidden rounded-lg border md:block">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/50 text-left text-xs text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">
                                                Member
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Kontak
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Membership
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Ditugaskan
                                            </th>
                                            {canAssign && (
                                                <th className="w-24 px-4 py-3 text-right font-medium">
                                                    Aksi
                                                </th>
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {assignedMembers.data.map((member) => (
                                            <AssignedMemberRow
                                                key={member.id}
                                                trainerId={trainer.id}
                                                member={member}
                                                canAssign={canAssign}
                                            />
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="grid gap-3 md:hidden">
                                {assignedMembers.data.map((member) => (
                                    <AssignedMemberCard
                                        key={member.id}
                                        trainerId={trainer.id}
                                        member={member}
                                        canAssign={canAssign}
                                    />
                                ))}
                            </div>

                            <AssignmentPagination
                                links={assignedMembers.links}
                                from={assignedMembers.from}
                                to={assignedMembers.to}
                                total={assignedMembers.total}
                            />
                        </>
                    )}
                </section>
            </div>
        </>
    );
}

function InfoItem({
    icon,
    label,
    value,
}: {
    icon: ReactNode;
    label: string;
    value: string;
}) {
    return (
        <div className="bg-background p-4">
            <div className="flex items-center gap-2 text-xs font-medium text-muted-foreground [&_svg]:size-4">
                {icon}
                {label}
            </div>
            <p className="mt-2 truncate text-sm font-medium" title={value}>
                {value}
            </p>
        </div>
    );
}

function AssignedMemberRow({
    trainerId,
    member,
    canAssign,
}: {
    trainerId: number;
    member: TrainerMember;
    canAssign: boolean;
}) {
    return (
        <tr className="hover:bg-muted/30">
            <td className="px-4 py-3">
                <div className="flex items-center gap-2">
                    <div>
                        <p className="font-medium">{member.name}</p>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {member.member_number}
                        </p>
                    </div>
                    <MemberStatusBadge
                        status={member.status}
                        label={member.status_label}
                    />
                </div>
            </td>
            <td className="px-4 py-3 text-muted-foreground">
                <p>{member.phone}</p>
                <p className="mt-0.5 text-xs">{member.email ?? '—'}</p>
            </td>
            <td className="px-4 py-3">
                {member.membership ? (
                    <div>
                        <p>{member.membership.plan_name}</p>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            Berlaku sampai{' '}
                            {formatDate(member.membership.end_date)}
                        </p>
                    </div>
                ) : (
                    <Badge variant="outline">Tidak aktif</Badge>
                )}
            </td>
            <td className="px-4 py-3 text-muted-foreground">
                {formatDateTime(member.assigned_at ?? null)}
            </td>
            {canAssign && (
                <td className="px-4 py-3 text-right">
                    <TrainerUnassignDialog
                        trainerId={trainerId}
                        memberId={member.id}
                        memberName={member.name}
                    />
                </td>
            )}
        </tr>
    );
}

function AssignedMemberCard({
    trainerId,
    member,
    canAssign,
}: {
    trainerId: number;
    member: TrainerMember;
    canAssign: boolean;
}) {
    return (
        <article className="rounded-lg border p-4">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="font-medium">{member.name}</p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {member.member_number}
                    </p>
                </div>
                <MemberStatusBadge
                    status={member.status}
                    label={member.status_label}
                />
            </div>
            <div className="mt-4 grid gap-2 text-sm text-muted-foreground">
                <p className="flex items-center gap-2">
                    <Phone className="size-4" />
                    {member.phone}
                </p>
                <p className="flex items-center gap-2">
                    <CalendarClock className="size-4" />
                    {member.membership
                        ? `${member.membership.plan_name} · ${formatDate(member.membership.end_date)}`
                        : 'Membership tidak aktif'}
                </p>
            </div>
            {canAssign && (
                <div className="mt-3 flex justify-end border-t pt-3">
                    <TrainerUnassignDialog
                        trainerId={trainerId}
                        memberId={member.id}
                        memberName={member.name}
                    />
                </div>
            )}
        </article>
    );
}

function AssignmentPagination({
    links,
    from,
    to,
    total,
}: {
    links: PaginatedTrainerMembers['links'];
    from: number | null;
    to: number | null;
    total: number;
}) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav className="flex flex-col items-center justify-between gap-3 border-t pt-4 text-sm sm:flex-row">
            <p className="text-muted-foreground">
                Menampilkan {from ?? 0}–{to ?? 0} dari {total} assignment
            </p>
            <div className="flex gap-2">
                <PageButton
                    url={links.at(0)?.url ?? null}
                    label="Sebelumnya"
                    icon={<ChevronLeft />}
                />
                <PageButton
                    url={links.at(-1)?.url ?? null}
                    label="Berikutnya"
                    icon={<ChevronRight />}
                    iconAfter
                />
            </div>
        </nav>
    );
}

function PageButton({
    url,
    label,
    icon,
    iconAfter = false,
}: {
    url: string | null;
    label: string;
    icon: ReactNode;
    iconAfter?: boolean;
}) {
    return (
        <Button
            variant="outline"
            size="sm"
            disabled={!url}
            asChild={Boolean(url)}
        >
            {url ? (
                <Link href={url} preserveScroll>
                    {!iconAfter && icon}
                    {label}
                    {iconAfter && icon}
                </Link>
            ) : (
                <span>
                    {!iconAfter && icon}
                    {label}
                    {iconAfter && icon}
                </span>
            )}
        </Button>
    );
}

ShowTrainer.layout = {
    breadcrumbs: [
        { title: 'Trainer', href: index() },
        { title: 'Detail trainer', href: '#' },
    ],
};
