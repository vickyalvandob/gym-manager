import { Form, usePage } from '@inertiajs/react';
import { Banknote, Dumbbell } from 'lucide-react';
import { useMemo, useState } from 'react';
import PurchaseMemberPtPackageController from '@/actions/App/Http/Controllers/PurchaseMemberPtPackageController';
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
import { formatCurrency } from '@/lib/formatters';
import type { PtPackageOption, PtTrainerOption, SelectOption } from '@/types';

export function PtPurchaseDialog({
    memberId,
    memberName,
    packages,
    trainers,
    paymentMethods,
    defaultTrainerId,
    defaultStartDate,
    triggerVariant = 'default',
}: {
    memberId: number;
    memberName: string;
    packages: PtPackageOption[];
    trainers: PtTrainerOption[];
    paymentMethods: SelectOption[];
    defaultTrainerId: number | null;
    defaultStartDate: string;
    triggerVariant?: 'default' | 'outline' | 'ghost';
}) {
    const { auth } = usePage().props;
    const [packageId, setPackageId] = useState(packages[0]?.id ?? 0);
    const selectedPackage = useMemo(
        () => packages.find((item) => item.id === packageId) ?? null,
        [packageId, packages],
    );
    const unavailable = packages.length === 0 || trainers.length === 0;

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    size="sm"
                    variant={triggerVariant}
                    disabled={unavailable}
                >
                    <Dumbbell />
                    Beli paket PT
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Beli paket Personal Training</DialogTitle>
                    <DialogDescription>
                        Aktifkan paket PT untuk {memberName} dan catat
                        pembayaran dalam satu transaksi.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...PurchaseMemberPtPackageController.form(memberId)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                >
                    {({ errors, processing }) => (
                        <div className="grid gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="pt_package_id">Paket PT</Label>
                                <select
                                    id="pt_package_id"
                                    name="pt_package_id"
                                    value={packageId || ''}
                                    onChange={(event) =>
                                        setPackageId(Number(event.target.value))
                                    }
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    required
                                >
                                    {packages.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.name} · {item.session_count}{' '}
                                            sesi
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.pt_package_id} />
                            </div>

                            {selectedPackage && (
                                <div className="grid grid-cols-2 gap-3 rounded-md border bg-muted/30 p-4 text-sm">
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Sesi
                                        </p>
                                        <p className="mt-1 font-semibold">
                                            {selectedPackage.session_count} sesi
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Masa berlaku
                                        </p>
                                        <p className="mt-1 font-semibold">
                                            {selectedPackage.validity_days
                                                ? `${selectedPackage.validity_days} hari`
                                                : 'Tanpa batas'}
                                        </p>
                                    </div>
                                    <div className="col-span-2 border-t pt-3">
                                        <p className="text-xs text-muted-foreground">
                                            Harga
                                        </p>
                                        <p className="mt-1 text-lg font-semibold tabular-nums">
                                            {formatCurrency(
                                                selectedPackage.price,
                                                auth.currentGym?.currency ??
                                                    'IDR',
                                            )}
                                        </p>
                                    </div>
                                </div>
                            )}

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="trainer_id">Trainer</Label>
                                    <select
                                        id="trainer_id"
                                        name="trainer_id"
                                        defaultValue={
                                            defaultTrainerId ??
                                            trainers[0]?.id ??
                                            ''
                                        }
                                        className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        required
                                    >
                                        {trainers.map((trainer) => (
                                            <option
                                                key={trainer.id}
                                                value={trainer.id}
                                            >
                                                {trainer.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.trainer_id} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="pt_start_date">
                                        Tanggal mulai
                                    </Label>
                                    <Input
                                        id="pt_start_date"
                                        name="start_date"
                                        type="date"
                                        defaultValue={defaultStartDate}
                                        min={defaultStartDate}
                                        required
                                    />
                                    <InputError message={errors.start_date} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="pt_payment_method">
                                    Metode pembayaran
                                </Label>
                                <select
                                    id="pt_payment_method"
                                    name="payment_method"
                                    defaultValue={
                                        paymentMethods[0]?.value ?? ''
                                    }
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    required
                                >
                                    {paymentMethods.map((method) => (
                                        <option
                                            key={method.value}
                                            value={method.value}
                                        >
                                            {method.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.payment_method} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="pt_purchase_notes">
                                    Catatan (opsional)
                                </Label>
                                <textarea
                                    id="pt_purchase_notes"
                                    name="notes"
                                    rows={3}
                                    maxLength={2000}
                                    className="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <InputError message={errors.member} />
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Batal
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    disabled={processing || unavailable}
                                    data-test="pay-activate-pt"
                                >
                                    <Banknote />
                                    {processing
                                        ? 'Memproses...'
                                        : 'Bayar & aktifkan'}
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
