import { Form } from '@inertiajs/react';
import { CalendarPlus } from 'lucide-react';
import PtSessionController from '@/actions/App/Http/Controllers/PtSessionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function PtScheduleDialog({
    memberPtPackageId,
    memberName,
    packageName,
    availableSessions,
    defaultDate,
    triggerLabel = 'Jadwalkan sesi',
}: {
    memberPtPackageId: number;
    memberName: string;
    packageName: string;
    availableSessions: number;
    defaultDate: string;
    triggerLabel?: string;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    size="sm"
                    disabled={availableSessions < 1}
                >
                    <CalendarPlus />
                    {triggerLabel}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Jadwalkan sesi PT</DialogTitle>
                    <DialogDescription>
                        {memberName} · {packageName} · {availableSessions} sesi
                        tersedia
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...PtSessionController.store.form()}
                    options={{ preserveScroll: true }}
                >
                    {({ errors, processing }) => (
                        <div className="grid gap-5">
                            <input
                                type="hidden"
                                name="member_pt_package_id"
                                value={memberPtPackageId}
                            />
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`pt-date-${memberPtPackageId}`}
                                    >
                                        Tanggal
                                    </Label>
                                    <Input
                                        id={`pt-date-${memberPtPackageId}`}
                                        name="date"
                                        type="date"
                                        min={defaultDate}
                                        defaultValue={defaultDate}
                                        required
                                    />
                                    <InputError message={errors.date} />
                                </div>
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`pt-time-${memberPtPackageId}`}
                                    >
                                        Waktu mulai
                                    </Label>
                                    <Input
                                        id={`pt-time-${memberPtPackageId}`}
                                        name="start_time"
                                        type="time"
                                        defaultValue="18:00"
                                        required
                                    />
                                    <InputError message={errors.start_time} />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`pt-duration-${memberPtPackageId}`}
                                >
                                    Durasi
                                </Label>
                                <select
                                    id={`pt-duration-${memberPtPackageId}`}
                                    name="duration_minutes"
                                    defaultValue="60"
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    {[30, 45, 60, 90, 120].map((duration) => (
                                        <option key={duration} value={duration}>
                                            {duration} menit
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.duration_minutes} />
                            </div>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`pt-notes-${memberPtPackageId}`}
                                >
                                    Catatan (opsional)
                                </Label>
                                <textarea
                                    id={`pt-notes-${memberPtPackageId}`}
                                    name="notes"
                                    rows={3}
                                    maxLength={2000}
                                    className="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                <InputError message={errors.notes} />
                            </div>
                            <InputError message={errors.member_pt_package_id} />
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Batal
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    <CalendarPlus />
                                    {processing ? 'Menyimpan...' : 'Jadwalkan'}
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
