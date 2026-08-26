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
        <Form {...form} options={{ preserveScroll: true }}>
            {({ errors, processing }) => (
                <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <div className="min-w-0 divide-y border-y">
                        <section className="grid gap-5 py-6 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <h2 className="text-base font-semibold">
                                    Informasi paket PT
                                </h2>
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Nama paket</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={ptPackage?.name ?? ''}
                                    maxLength={120}
                                    placeholder="Contoh: PT Regular"
                                    autoFocus
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="session_count">
                                    Jumlah sesi
                                </Label>
                                <Input
                                    id="session_count"
                                    name="session_count"
                                    type="number"
                                    min={1}
                                    max={1000}
                                    step={1}
                                    defaultValue={ptPackage?.session_count ?? 8}
                                    required
                                />
                                <InputError message={errors.session_count} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="validity_days">
                                    Masa berlaku (hari)
                                </Label>
                                <Input
                                    id="validity_days"
                                    name="validity_days"
                                    type="number"
                                    min={1}
                                    max={3650}
                                    step={1}
                                    defaultValue={
                                        ptPackage?.validity_days ?? ''
                                    }
                                    placeholder="Kosong = tanpa batas"
                                />
                                <InputError message={errors.validity_days} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="price">Harga</Label>
                                <Input
                                    id="price"
                                    name="price"
                                    type="number"
                                    min={0}
                                    max={999999999999.99}
                                    step="0.01"
                                    defaultValue={ptPackage?.price ?? ''}
                                    placeholder="900000"
                                    required
                                />
                                <InputError message={errors.price} />
                            </div>
                        </section>
                        <section className="grid gap-3 py-6">
                            <Label htmlFor="description">Deskripsi</Label>
                            <textarea
                                id="description"
                                name="description"
                                rows={5}
                                maxLength={2000}
                                defaultValue={ptPackage?.description ?? ''}
                                className="min-h-32 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            <InputError message={errors.description} />
                        </section>
                    </div>

                    <aside className="space-y-6">
                        <div className="border-y py-5">
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="pt_package_active"
                                    checked={isActive}
                                    onCheckedChange={(checked) =>
                                        setIsActive(checked === true)
                                    }
                                />
                                <div>
                                    <Label htmlFor="pt_package_active">
                                        Paket aktif
                                    </Label>
                                    <p className="mt-1 text-xs leading-5 text-muted-foreground">
                                        Hanya paket aktif yang dapat dijual.
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
                        <div className="flex gap-2">
                            <Button
                                type="submit"
                                disabled={processing}
                                className="flex-1"
                                data-test="save-pt-package"
                            >
                                <Save />
                                {processing ? 'Menyimpan...' : submitLabel}
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={cancelHref}>Batal</Link>
                            </Button>
                        </div>
                    </aside>
                </div>
            )}
        </Form>
    );
}
