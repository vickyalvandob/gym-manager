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
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export function MembershipPlanForm({
    form,
    membershipPlan,
    durationOptions,
    cancelHref,
    submitLabel,
}: MembershipPlanFormProps) {
    const [isActive, setIsActive] = useState(membershipPlan?.is_active ?? true);

    return (
        <Form {...form} options={{ preserveScroll: true }}>
            {({ errors, processing }) => (
                <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <div className="min-w-0 divide-y border-y">
                        <section className="grid gap-5 py-6 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <h2 className="text-base font-semibold">
                                    Informasi paket
                                </h2>
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Nama paket</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={membershipPlan?.name ?? ''}
                                    required
                                    maxLength={120}
                                    autoFocus
                                    placeholder="Contoh: Monthly"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="duration">Durasi</Label>
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
                                    Satuan durasi
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
                                <Label htmlFor="price">Harga</Label>
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
                                <InputError message={errors.price} />
                            </div>
                        </section>

                        <section className="grid gap-5 py-6">
                            <div>
                                <h2 className="text-base font-semibold">
                                    Deskripsi
                                </h2>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    Keterangan paket
                                </Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    defaultValue={
                                        membershipPlan?.description ?? ''
                                    }
                                    maxLength={2000}
                                    rows={5}
                                    className="min-h-32 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                />
                                <InputError message={errors.description} />
                            </div>
                        </section>
                    </div>

                    <aside className="space-y-6">
                        <div className="border-y py-5">
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="is_active_control"
                                    checked={isActive}
                                    onCheckedChange={(checked) =>
                                        setIsActive(checked === true)
                                    }
                                />
                                <div className="grid gap-1">
                                    <Label htmlFor="is_active_control">
                                        Paket aktif
                                    </Label>
                                    <p className="text-sm text-muted-foreground">
                                        Paket aktif dapat dipilih saat transaksi
                                        membership.
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
                                data-test="save-membership-plan"
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
