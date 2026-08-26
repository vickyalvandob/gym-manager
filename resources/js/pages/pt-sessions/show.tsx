import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarSync,
    CheckCircle2,
    UserX,
    XCircle,
} from 'lucide-react';
import CancelPtSessionController from '@/actions/App/Http/Controllers/CancelPtSessionController';
import CompletePtSessionController from '@/actions/App/Http/Controllers/CompletePtSessionController';
import MarkPtSessionNoShowController from '@/actions/App/Http/Controllers/MarkPtSessionNoShowController';
import PtSessionController from '@/actions/App/Http/Controllers/PtSessionController';
import ReschedulePtSessionController from '@/actions/App/Http/Controllers/ReschedulePtSessionController';
import InputError from '@/components/input-error';
import { PtSessionStatusBadge } from '@/components/personal-training/pt-session-status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, formatDateTime } from '@/lib/formatters';
import type { PtSessionDetail } from '@/types';

export default function ShowPtSession({
    session,
    canComplete,
    canNoShow,
    canEdit,
}: {
    session: PtSessionDetail;
    canComplete: boolean;
    canNoShow: boolean;
    canEdit: boolean;
}) {
    const { auth } = usePage().props;
    const local = new Date(session.scheduled_at);
    const date = new Intl.DateTimeFormat('en-CA', {
        timeZone: auth.currentGym?.timezone,
    }).format(local);
    const time = new Intl.DateTimeFormat('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: auth.currentGym?.timezone,
    }).format(local);
    const scheduled = session.status === 'scheduled';

    return (
        <>
            <Head title={`Sesi PT ${session.member.name}`} />
            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-7 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex items-start gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={PtSessionController.index()}>
                            <ArrowLeft />
                            <span className="sr-only">Kembali</span>
                        </Link>
                    </Button>
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-2xl font-semibold">
                                {session.member.name}
                            </h1>
                            <PtSessionStatusBadge
                                status={session.status}
                                label={session.status_label}
                            />
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {session.member.member_number} ·{' '}
                            {session.pt_package.name}
                        </p>
                    </div>
                </header>
                <div className="grid gap-7 lg:grid-cols-[1fr_20rem]">
                    <main className="space-y-7">
                        <section className="border-y py-6">
                            <h2 className="text-base font-semibold">
                                Informasi sesi
                            </h2>
                            <dl className="mt-5 grid gap-4 sm:grid-cols-2">
                                <Item
                                    label="Jadwal"
                                    value={formatDateTime(
                                        session.scheduled_at,
                                        auth.currentGym?.timezone,
                                    )}
                                />
                                <Item
                                    label="Durasi"
                                    value={`${session.duration_minutes} menit`}
                                />
                                <Item
                                    label="Trainer"
                                    value={session.trainer.name}
                                />
                                <Item
                                    label="Telepon member"
                                    value={session.member.phone}
                                />
                            </dl>
                            {session.notes && (
                                <div className="mt-5 border-t pt-4">
                                    <p className="text-xs text-muted-foreground">
                                        Catatan
                                    </p>
                                    <p className="mt-2 text-sm whitespace-pre-wrap">
                                        {session.notes}
                                    </p>
                                </div>
                            )}
                            {session.cancellation_reason && (
                                <div className="mt-5 border-t pt-4">
                                    <p className="text-xs text-muted-foreground">
                                        Alasan pembatalan
                                    </p>
                                    <p className="mt-2 text-sm">
                                        {session.cancellation_reason}
                                    </p>
                                </div>
                            )}
                        </section>
                        {scheduled && canEdit && (
                            <section className="border-y py-6">
                                <h2 className="flex items-center gap-2 text-base font-semibold">
                                    <CalendarSync className="size-4" />
                                    Ubah jadwal
                                </h2>
                                <Form
                                    {...ReschedulePtSessionController.form(
                                        session.id,
                                    )}
                                    options={{ preserveScroll: true }}
                                    className="mt-5 grid gap-4 sm:grid-cols-2"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="reschedule_date">
                                                    Tanggal
                                                </Label>
                                                <Input
                                                    id="reschedule_date"
                                                    name="date"
                                                    type="date"
                                                    defaultValue={date}
                                                    required
                                                />
                                                <InputError
                                                    message={errors.date}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="reschedule_time">
                                                    Waktu
                                                </Label>
                                                <Input
                                                    id="reschedule_time"
                                                    name="start_time"
                                                    type="time"
                                                    defaultValue={time}
                                                    required
                                                />
                                                <InputError
                                                    message={errors.start_time}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="reschedule_duration">
                                                    Durasi
                                                </Label>
                                                <Input
                                                    id="reschedule_duration"
                                                    name="duration_minutes"
                                                    type="number"
                                                    min={30}
                                                    max={240}
                                                    defaultValue={
                                                        session.duration_minutes
                                                    }
                                                    required
                                                />
                                                <InputError
                                                    message={
                                                        errors.duration_minutes
                                                    }
                                                />
                                            </div>
                                            <div className="flex items-end">
                                                <Button
                                                    type="submit"
                                                    variant="outline"
                                                    disabled={processing}
                                                >
                                                    <CalendarSync />
                                                    Simpan jadwal
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </section>
                        )}
                    </main>
                    <aside className="space-y-6">
                        <section className="border-y py-5">
                            <p className="text-xs text-muted-foreground">
                                Quota paket
                            </p>
                            <p className="mt-2 text-2xl font-semibold">
                                {session.package.available_sessions}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                sesi masih tersedia
                            </p>
                            <dl className="mt-4 space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <dt>Terpakai</dt>
                                    <dd>
                                        {session.package.used_sessions}/
                                        {session.package.total_sessions}
                                    </dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt>Berlaku sampai</dt>
                                    <dd>
                                        {formatDate(session.package.expires_at)}
                                    </dd>
                                </div>
                            </dl>
                        </section>
                        {scheduled && (
                            <section className="grid gap-2">
                                {canComplete && (
                                    <ActionForm
                                        form={CompletePtSessionController.form(
                                            session.id,
                                        )}
                                        label="Tandai selesai"
                                        icon={<CheckCircle2 />}
                                    />
                                )}
                                {canNoShow && (
                                    <ActionForm
                                        form={MarkPtSessionNoShowController.form(
                                            session.id,
                                        )}
                                        label="Tandai no-show"
                                        icon={<UserX />}
                                        variant="outline"
                                    />
                                )}
                                {canEdit && (
                                    <Form
                                        {...CancelPtSessionController.form(
                                            session.id,
                                        )}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ errors, processing }) => (
                                            <div className="grid gap-2">
                                                <Label htmlFor="cancellation_reason">
                                                    Alasan pembatalan
                                                </Label>
                                                <textarea
                                                    id="cancellation_reason"
                                                    name="cancellation_reason"
                                                    rows={3}
                                                    maxLength={2000}
                                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                />
                                                <InputError
                                                    message={
                                                        errors.cancellation_reason
                                                    }
                                                />
                                                <Button
                                                    type="submit"
                                                    variant="destructive"
                                                    disabled={processing}
                                                >
                                                    <XCircle />
                                                    Batalkan sesi
                                                </Button>
                                            </div>
                                        )}
                                    </Form>
                                )}
                            </section>
                        )}
                    </aside>
                </div>
            </div>
        </>
    );
}

function Item({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="mt-1 font-medium">{value}</dd>
        </div>
    );
}
function ActionForm({
    form,
    label,
    icon,
    variant = 'default',
}: {
    form: { action: string; method: 'post' };
    label: string;
    icon: React.ReactNode;
    variant?: 'default' | 'outline';
}) {
    return (
        <Form {...form} options={{ preserveScroll: true }}>
            {({ processing }) => (
                <Button
                    type="submit"
                    variant={variant}
                    disabled={processing}
                    className="w-full"
                >
                    {icon}
                    {label}
                </Button>
            )}
        </Form>
    );
}
ShowPtSession.layout = {
    breadcrumbs: [
        { title: 'Sesi PT', href: PtSessionController.index() },
        { title: 'Detail sesi', href: '#' },
    ],
};
