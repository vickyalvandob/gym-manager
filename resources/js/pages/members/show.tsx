import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    Mail,
    MapPin,
    Pencil,
    Phone,
    ShieldAlert,
    UserRound,
} from 'lucide-react';
import type { ReactNode } from 'react';
import MemberController from '@/actions/App/Http/Controllers/MemberController';
import { MemberAvatar } from '@/components/members/member-avatar';
import { MemberStatusBadge } from '@/components/members/member-status-badge';
import { MemberStatusDialog } from '@/components/members/member-status-dialog';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/members';
import type { MemberDetail } from '@/types';

function formatDate(value: string | null) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'Asia/Jakarta',
    }).format(new Date(value));
}

export default function ShowMember({ member }: { member: MemberDetail }) {
    return (
        <>
            <Head title={member.name} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
                    <div className="flex min-w-0 items-start gap-4">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="mt-1 shrink-0"
                            title="Kembali ke member"
                            asChild
                        >
                            <Link href={index()}>
                                <ArrowLeft />
                                <span className="sr-only">
                                    Kembali ke member
                                </span>
                            </Link>
                        </Button>
                        <MemberAvatar
                            name={member.name}
                            photoUrl={member.photo_url}
                            className="size-14 rounded-lg"
                        />
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <h1 className="truncate text-2xl font-semibold tracking-normal">
                                    {member.name}
                                </h1>
                                <MemberStatusBadge
                                    status={member.status}
                                    label={member.status_label}
                                />
                            </div>
                            <p className="mt-1 font-mono text-sm text-muted-foreground">
                                {member.member_number}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2 pl-14 sm:pl-0">
                        <MemberStatusDialog
                            memberId={member.id}
                            memberName={member.name}
                            status={member.status}
                        />
                        <Button variant="outline" size="sm" asChild>
                            <Link href={MemberController.edit(member.id)}>
                                <Pencil />
                                Edit
                            </Link>
                        </Button>
                    </div>
                </header>

                <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <main className="min-w-0 divide-y border-y">
                        <section className="py-6">
                            <h2 className="text-base font-semibold">Profil</h2>
                            <dl className="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                                <DetailItem
                                    icon={<Phone />}
                                    label="Nomor telepon"
                                    value={member.phone}
                                    href={`tel:${member.phone}`}
                                />
                                <DetailItem
                                    icon={<Mail />}
                                    label="Email"
                                    value={member.email ?? '-'}
                                    href={
                                        member.email
                                            ? `mailto:${member.email}`
                                            : undefined
                                    }
                                />
                                <DetailItem
                                    icon={<UserRound />}
                                    label="Jenis kelamin"
                                    value={member.gender_label ?? '-'}
                                />
                                <DetailItem
                                    icon={<CalendarDays />}
                                    label="Tanggal lahir"
                                    value={formatDate(member.birth_date)}
                                />
                                <DetailItem
                                    icon={<ShieldAlert />}
                                    label="Kontak darurat"
                                    value={member.emergency_contact ?? '-'}
                                />
                                <DetailItem
                                    icon={<MapPin />}
                                    label="Alamat"
                                    value={member.address ?? '-'}
                                />
                            </dl>
                        </section>

                        <section className="py-6">
                            <h2 className="text-base font-semibold">Catatan</h2>
                            <p className="mt-3 text-sm leading-6 whitespace-pre-wrap text-muted-foreground">
                                {member.notes ?? 'Tidak ada catatan.'}
                            </p>
                        </section>
                    </main>

                    <aside className="space-y-7">
                        <section>
                            <h2 className="text-base font-semibold">
                                Keanggotaan
                            </h2>
                            <div className="mt-4 border-y py-4">
                                <p className="text-sm font-medium">
                                    Belum ada paket aktif
                                </p>
                            </div>
                        </section>

                        <section>
                            <h2 className="text-base font-semibold">
                                Riwayat data
                            </h2>
                            <dl className="mt-4 divide-y border-y text-sm">
                                <div className="py-3">
                                    <dt className="text-muted-foreground">
                                        Terdaftar
                                    </dt>
                                    <dd className="mt-1 font-medium">
                                        {formatDate(member.created_at)}
                                    </dd>
                                </div>
                                <div className="py-3">
                                    <dt className="text-muted-foreground">
                                        Diperbarui
                                    </dt>
                                    <dd className="mt-1 font-medium">
                                        {formatDate(member.updated_at)}
                                    </dd>
                                </div>
                            </dl>
                        </section>
                    </aside>
                </div>
            </div>
        </>
    );
}

function DetailItem({
    icon,
    label,
    value,
    href,
}: {
    icon: ReactNode;
    label: string;
    value: string;
    href?: string;
}) {
    const content = href ? (
        <a href={href} className="font-medium break-words hover:text-primary">
            {value}
        </a>
    ) : (
        <span className="font-medium break-words">{value}</span>
    );

    return (
        <div className="flex gap-3">
            <span className="mt-0.5 text-muted-foreground [&_svg]:size-4">
                {icon}
            </span>
            <div className="min-w-0">
                <dt className="text-xs text-muted-foreground">{label}</dt>
                <dd className="mt-1 text-sm">{content}</dd>
            </div>
        </div>
    );
}

ShowMember.layout = {
    breadcrumbs: [
        { title: 'Member', href: index() },
        { title: 'Detail member', href: index() },
    ],
};
