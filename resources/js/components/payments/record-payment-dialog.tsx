import { Form } from '@inertiajs/react';
import { Banknote } from 'lucide-react';
import MarkPaymentPaidController from '@/actions/App/Http/Controllers/MarkPaymentPaidController';
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
import { Label } from '@/components/ui/label';
import { formatCurrency } from '@/lib/formatters';
import type { MembershipPayment, SelectOption } from '@/types';

export function RecordPaymentDialog({
    payment,
    currency,
    methodOptions,
    compact = false,
}: {
    payment: MembershipPayment;
    currency: string;
    methodOptions: SelectOption[];
    compact?: boolean;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button" size="sm" title="Catat pembayaran">
                    <Banknote />
                    <span className={compact ? 'sr-only' : ''}>
                        Catat bayar
                    </span>
                </Button>
            </DialogTrigger>
            <DialogContent className="rounded-lg shadow-md">
                <DialogHeader>
                    <DialogTitle>Catat pembayaran</DialogTitle>
                    <DialogDescription>
                        Konfirmasi pembayaran penuh untuk{' '}
                        {payment.invoice_number}.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...MarkPaymentPaidController.form(payment.id)}
                    options={{ preserveScroll: true }}
                >
                    {({ errors, processing }) => (
                        <div className="grid gap-5">
                            <div className="rounded-md border bg-muted/30 p-3">
                                <p className="text-xs text-muted-foreground">
                                    Total pembayaran
                                </p>
                                <p className="mt-1 text-lg font-semibold tabular-nums">
                                    {formatCurrency(payment.amount, currency)}
                                </p>
                            </div>

                            {errors.payment && (
                                <p className="text-sm text-destructive">
                                    {errors.payment}
                                </p>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor={`payment-method-${payment.id}`}>
                                    Metode pembayaran
                                </Label>
                                <select
                                    id={`payment-method-${payment.id}`}
                                    name="method"
                                    defaultValue={methodOptions[0]?.value ?? ''}
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                    required
                                >
                                    {methodOptions.map((method) => (
                                        <option
                                            key={method.value}
                                            value={method.value}
                                        >
                                            {method.label}
                                        </option>
                                    ))}
                                </select>
                                {errors.method && (
                                    <p className="text-sm text-destructive">
                                        {errors.method}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`payment-notes-${payment.id}`}>
                                    Catatan (opsional)
                                </Label>
                                <textarea
                                    id={`payment-notes-${payment.id}`}
                                    name="notes"
                                    rows={3}
                                    maxLength={1000}
                                    className="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                    placeholder="Contoh: dibayar di front desk"
                                />
                                {errors.notes && (
                                    <p className="text-sm text-destructive">
                                        {errors.notes}
                                    </p>
                                )}
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Batal
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    <Banknote />
                                    {processing
                                        ? 'Menyimpan...'
                                        : 'Konfirmasi lunas'}
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
