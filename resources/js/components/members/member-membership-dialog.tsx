import { Form } from '@inertiajs/react';
import { BadgePlus, CalendarClock, RefreshCw } from 'lucide-react';
import { useMemo, useState } from 'react';
import AssignMemberMembershipController from '@/actions/App/Http/Controllers/AssignMemberMembershipController';
import RenewMemberMembershipController from '@/actions/App/Http/Controllers/RenewMemberMembershipController';
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
import { formatCurrency, formatDate } from '@/lib/formatters';
import type { MembershipPlanOption } from '@/types';

type MemberMembershipDialogProps = {
    memberId: number;
    memberName: string;
    currency: string;
    membershipPlans: MembershipPlanOption[];
    defaultPlanId: number | null;
    defaultStartDate: string;
    mode: 'assign' | 'renew';
    renewalSourceId?: number | null;
};

export function MemberMembershipDialog({
    memberId,
    memberName,
    currency,
    membershipPlans,
    defaultPlanId,
    defaultStartDate,
    mode,
    renewalSourceId = null,
}: MemberMembershipDialogProps) {
    const [selectedPlanId, setSelectedPlanId] = useState(
        String(defaultPlanId ?? membershipPlans[0]?.id ?? ''),
    );
    const [startDate, setStartDate] = useState(defaultStartDate);
    const selectedPlan = membershipPlans.find(
        (plan) => plan.id === Number(selectedPlanId),
    );
    const projectedEndDate = useMemo(
        () => calculateEndDate(startDate, selectedPlan),
        [selectedPlan, startDate],
    );
    const renewing = mode === 'renew';
    const form = renewing
        ? RenewMemberMembershipController.form([memberId, renewalSourceId ?? 0])
        : AssignMemberMembershipController.form(memberId);

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    size="sm"
                    variant={renewing ? 'outline' : 'default'}
                    disabled={
                        membershipPlans.length === 0 ||
                        (renewing && !renewalSourceId)
                    }
                    title={
                        membershipPlans.length === 0
                            ? 'Aktifkan paket membership terlebih dahulu'
                            : undefined
                    }
                >
                    {renewing ? <RefreshCw /> : <BadgePlus />}
                    {renewing ? 'Perpanjang' : 'Tetapkan paket'}
                </Button>
            </DialogTrigger>
            <DialogContent className="rounded-lg shadow-md">
                <DialogHeader>
                    <DialogTitle>
                        {renewing
                            ? 'Perpanjang membership'
                            : 'Tetapkan membership'}
                    </DialogTitle>
                    <DialogDescription>
                        Pilih paket dan tanggal mulai untuk {memberName}.
                        Tanggal akhir dihitung otomatis dari durasi paket.
                    </DialogDescription>
                </DialogHeader>

                <Form {...form} options={{ preserveScroll: true }}>
                    {({ errors, processing }) => (
                        <div className="grid gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor={`${mode}-membership-plan`}>
                                    Paket membership
                                </Label>
                                <select
                                    id={`${mode}-membership-plan`}
                                    name="membership_plan_id"
                                    value={selectedPlanId}
                                    onChange={(event) =>
                                        setSelectedPlanId(event.target.value)
                                    }
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                    required
                                >
                                    {membershipPlans.map((plan) => (
                                        <option key={plan.id} value={plan.id}>
                                            {plan.name} · {plan.duration_label}
                                        </option>
                                    ))}
                                </select>
                                {errors.membership_plan_id && (
                                    <p className="text-sm text-destructive">
                                        {errors.membership_plan_id}
                                    </p>
                                )}
                                {selectedPlan && (
                                    <p className="text-xs text-muted-foreground">
                                        {formatCurrency(
                                            selectedPlan.price,
                                            currency,
                                        )}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`${mode}-start-date`}>
                                    Tanggal mulai
                                </Label>
                                <Input
                                    id={`${mode}-start-date`}
                                    name="start_date"
                                    type="date"
                                    value={startDate}
                                    onChange={(event) =>
                                        setStartDate(event.target.value)
                                    }
                                    required
                                />
                                {errors.start_date && (
                                    <p className="text-sm text-destructive">
                                        {errors.start_date}
                                    </p>
                                )}
                            </div>

                            {projectedEndDate && (
                                <div className="flex gap-3 rounded-md border bg-muted/30 p-3 text-sm">
                                    <CalendarClock className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                    <div>
                                        <p className="font-medium">
                                            Berakhir{' '}
                                            {formatDate(projectedEndDate)}
                                        </p>
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            Periode termasuk tanggal mulai dan
                                            tanggal akhir.
                                        </p>
                                    </div>
                                </div>
                            )}

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Batal
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {renewing ? <RefreshCw /> : <BadgePlus />}
                                    {processing
                                        ? 'Menyimpan...'
                                        : renewing
                                          ? 'Simpan renewal'
                                          : 'Tetapkan paket'}
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function calculateEndDate(
    startDate: string,
    plan: MembershipPlanOption | undefined,
): string | null {
    if (!startDate || !plan) {
        return null;
    }

    const date = new Date(`${startDate}T00:00:00Z`);

    if (plan.duration_unit === 'day') {
        date.setUTCDate(date.getUTCDate() + plan.duration);
    } else if (plan.duration_unit === 'week') {
        date.setUTCDate(date.getUTCDate() + plan.duration * 7);
    } else if (plan.duration_unit === 'month') {
        addMonthsNoOverflow(date, plan.duration);
    } else {
        addYearsNoOverflow(date, plan.duration);
    }

    date.setUTCDate(date.getUTCDate() - 1);

    return date.toISOString().slice(0, 10);
}

function addMonthsNoOverflow(date: Date, months: number) {
    const day = date.getUTCDate();
    const targetMonth = date.getUTCMonth() + months;
    const targetYear = date.getUTCFullYear() + Math.floor(targetMonth / 12);
    const normalizedMonth = ((targetMonth % 12) + 12) % 12;
    const lastDay = new Date(
        Date.UTC(targetYear, normalizedMonth + 1, 0),
    ).getUTCDate();

    date.setUTCFullYear(targetYear, normalizedMonth, Math.min(day, lastDay));
}

function addYearsNoOverflow(date: Date, years: number) {
    const day = date.getUTCDate();
    const month = date.getUTCMonth();
    const targetYear = date.getUTCFullYear() + years;
    const lastDay = new Date(Date.UTC(targetYear, month + 1, 0)).getUTCDate();

    date.setUTCFullYear(targetYear, month, Math.min(day, lastDay));
}
