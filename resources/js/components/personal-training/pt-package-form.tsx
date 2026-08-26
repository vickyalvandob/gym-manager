import { Form, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { PtPackage } from '@/types';

export function PtPackageForm({
    form,
    ptPackage,
    cancelHref,
    submitLabel,
}: {
    form: { action: string; method: 'post' };
    ptPackage?: PtPackage;
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    submitLabel: string;
}) {
    const [isActive, setIsActive] = useState(ptPackage?.is_active ?? true);

    return (
        <Form
            {...form}
            options={{ preserveScroll: true }}
            className="max-w-3xl"
        >
            {({ errors, processing }) => (
                <div className="grid gap-5">
                    <section className="rounded-lg border p-5 sm:p-6">
                        <div>
                            <h2 className="text-base font-semibold">
                                Detail paket PT
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Atur jumlah sesi, masa berlaku, dan harga jual.
                            </p>
                        </div>

                        <div className="mt-5 grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Nama paket *</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={ptPackage?.name ?? ''}
                                    maxLength={120}
                                    placeholder="Contoh: PT Regular 8 Sesi"
                                    autoFocus
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="session_count">
                                    Jumlah sesi *
                                </Label>
                                <Input
                                    id="session_count"
                                    name="session_count"
                                    type="number"
                                    min={1}
                                    max={1000}
                                    step={1}
                                    inputMode="numeric"
                                    defaultValue={ptPackage?.session_count ?? 8}
                                    required
                                />
                                <InputError message={errors.session_count} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="validity_days">
                                    Masa berlaku
                                </Label>
                                <Input
                                    id="validity_days"
                                    name="validity_days"
                                    type="number"
                                    min={1}
                                    max={3650}
                                    step={1}
                                    inputMode="numeric"
                                    defaultValue={
                                        ptPackage?.validity_days ?? ''
                                    }
                                    placeholder="Contoh: 60 hari"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Kosongkan jika paket tidak memiliki batas
                                    hari.
                                </p>
                                <InputError message={errors.validity_days} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="price">Harga *</Label>
                                <Input
                                    id="price"
                                    name="price"
                                    type="number"
                                    min={0}
                                    max={999999999999.99}
                                    step="0.01"
                                    inputMode="decimal"
                                    defaultValue={ptPackage?.price ?? ''}
                                    placeholder="900000"
                                    required
                                />
                                <p className="text-xs text-muted-foreground">
                                    Masukkan angka tanpa pemisah ribuan.
                                </p>
                                <InputError message={errors.price} />
                            </div>

                            <div className="sm:col-span-2">
                                <div className="flex items-start gap-3 rounded-md bg-muted/40 p-4">
                                    <Checkbox
                                        id="pt_package_active"
                                        checked={isActive}
                                        onCheckedChange={(checked) =>
                                            setIsActive(checked === true)
                                        }
                                    />
                                    <div>
                                        <Label htmlFor="pt_package_active">
                                            Paket dapat dijual
                                        </Label>
                                        <p className="mt-1 text-xs leading-5 text-muted-foreground">
                                            Paket nonaktif tetap tersimpan untuk
                                            menjaga histori transaksi.
                                        </p>
                                    </div>
                                </div>
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value={isActive ? '1' : '0'}
                                />
                                <InputError
                                    message={errors.is_active}
                                    className="mt-2"
                                />
                            </div>
                        </div>
                    </section>

                    <section className="rounded-lg border p-5 sm:p-6">
                        <h2 className="text-base font-semibold">
                            Deskripsi paket
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Opsional; tulis manfaat atau ketentuan yang perlu
                            diketahui Front Desk.
                        </p>
                        <div className="mt-5 grid gap-2">
                            <Label htmlFor="description">Deskripsi</Label>
                            <textarea
                                id="description"
                                name="description"
                                rows={4}
                                maxLength={2000}
                                defaultValue={ptPackage?.description ?? ''}
                                className="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                placeholder="Contoh: Latihan privat dengan evaluasi progres."
                            />
                            <InputError message={errors.description} />
                        </div>
                    </section>

                    <div className="flex flex-col-reverse gap-2 border-t pt-5 sm:flex-row sm:justify-end">
                        <Button variant="outline" asChild>
                            <Link href={cancelHref}>Batal</Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="save-pt-package"
                        >
                            <Save />
                            {processing ? 'Menyimpan...' : submitLabel}
                        </Button>
                    </div>
                </div>
            )}
        </Form>
    );
}
