import { Form, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { MembershipPlan, SelectOption } from '@/types';

type MembershipPlanFormProps = {
    form: {
        action: string;
        method: 'post';
    };
    membershipPlan?: MembershipPlan;
    durationOptions: SelectOption[];
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    submitLabel: string;
};

const selectClassName =
    'h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export function MembershipPlanForm({
    form,
    membershipPlan,
    durationOptions,
    cancelHref,
    submitLabel,
}: MembershipPlanFormProps) {
    const [isActive, setIsActive] = useState(membershipPlan?.is_active ?? true);

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
                                Detail paket
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Tentukan nama, masa aktif, dan harga yang akan
                                dipilih saat mendaftarkan member.
                            </p>
                        </div>

                        <div className="mt-5 grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Nama paket *</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={membershipPlan?.name ?? ''}
                                    required
                                    maxLength={120}
                                    autoFocus
                                    placeholder="Contoh: Bulanan"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="duration">Durasi *</Label>
                                <Input
                                    id="duration"
                                    type="number"
                                    name="duration"
                                    defaultValue={membershipPlan?.duration ?? 1}
                                    required
                                    min={1}
                                    max={3650}
                                    step={1}
                                    inputMode="numeric"
                                />
                                <InputError message={errors.duration} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="duration_unit">
                                    Satuan durasi *
                                </Label>
                                <select
                                    id="duration_unit"
                                    name="duration_unit"
                                    defaultValue={
                                        membershipPlan?.duration_unit ?? 'month'
                                    }
                                    className={selectClassName}
                                    required
                                >
                                    {durationOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.duration_unit} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="price">Harga *</Label>
                                <Input
                                    id="price"
                                    type="number"
                                    name="price"
                                    defaultValue={membershipPlan?.price ?? ''}
                                    required
                                    min={0}
                                    max={999999999999.99}
                                    step="0.01"
                                    inputMode="decimal"
                                    placeholder="250000"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Masukkan angka tanpa pemisah ribuan.
                                </p>
                                <InputError message={errors.price} />
                            </div>

                            <div className="sm:col-span-2">
                                <div className="flex items-start gap-3 rounded-md bg-muted/40 p-4">
                                    <Checkbox
                                        id="is_active_control"
                                        checked={isActive}
                                        onCheckedChange={(checked) =>
                                            setIsActive(checked === true)
                                        }
                                    />
                                    <div className="grid gap-1">
                                        <Label htmlFor="is_active_control">
                                            Paket dapat dipilih
                                        </Label>
                                        <p className="text-xs leading-5 text-muted-foreground">
                                            Nonaktifkan untuk menyimpan histori
                                            tanpa menawarkan paket ke member
                                            baru.
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
                            Opsional; cukup tulis manfaat utama atau batasan
                            paket.
                        </p>
                        <div className="mt-5 grid gap-2">
                            <Label htmlFor="description">Deskripsi</Label>
                            <textarea
                                id="description"
                                name="description"
                                defaultValue={membershipPlan?.description ?? ''}
                                maxLength={2000}
                                rows={4}
                                className="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                placeholder="Contoh: Akses gym setiap hari selama jam operasional."
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
                            data-test="save-membership-plan"
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
