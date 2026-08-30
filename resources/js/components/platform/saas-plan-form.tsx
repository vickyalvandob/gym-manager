import { Form, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SaasPlan, SelectOption } from '@/types';

type Props = {
    form: { action: string; method: 'post' };
    plan?: SaasPlan;
    intervalOptions: SelectOption[];
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    submitLabel: string;
};

const selectClassName =
    'h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export function SaasPlanForm({
    form,
    plan,
    intervalOptions,
    cancelHref,
    submitLabel,
}: Props) {
    const [isActive, setIsActive] = useState(plan?.is_active ?? true);

    return (
        <Form {...form} className="max-w-4xl">
            {({ errors, processing }) => (
                <div className="grid gap-5">
                    <section className="rounded-xl border p-5 sm:p-6">
                        <h2 className="text-base font-semibold">
                            Detail paket
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Harga ini adalah tagihan gym kepada platform, bukan
                            harga membership member.
                        </p>
                        <div className="mt-5 grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Nama paket *</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={plan?.name ?? ''}
                                    required
                                    maxLength={120}
                                    autoFocus
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="price">Harga *</Label>
                                <Input
                                    id="price"
                                    name="price"
                                    type="number"
                                    defaultValue={plan?.price ?? ''}
                                    min={0}
                                    step="0.01"
                                    required
                                />
                                <InputError message={errors.price} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="billing_interval">
                                    Periode tagihan *
                                </Label>
                                <select
                                    id="billing_interval"
                                    name="billing_interval"
                                    defaultValue={
                                        plan?.billing_interval ?? 'monthly'
                                    }
                                    className={selectClassName}
                                >
                                    {intervalOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.billing_interval} />
                            </div>
                            <input type="hidden" name="currency" value="IDR" />
                            <div className="grid gap-2">
                                <Label htmlFor="trial_days">Durasi trial</Label>
                                <Input
                                    id="trial_days"
                                    name="trial_days"
                                    type="number"
                                    defaultValue={plan?.trial_days ?? 14}
                                    min={0}
                                    max={365}
                                    required
                                />
                                <InputError message={errors.trial_days} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="sort_order">
                                    Urutan tampil
                                </Label>
                                <Input
                                    id="sort_order"
                                    name="sort_order"
                                    type="number"
                                    defaultValue={plan?.sort_order ?? 0}
                                    min={0}
                                    max={65535}
                                    required
                                />
                                <InputError message={errors.sort_order} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="max_gyms">Batas gym</Label>
                                <Input
                                    id="max_gyms"
                                    name="max_gyms"
                                    type="number"
                                    defaultValue={plan?.max_gyms ?? ''}
                                    min={1}
                                    placeholder="Kosong berarti tanpa batas"
                                />
                                <InputError message={errors.max_gyms} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="max_members">
                                    Batas member
                                </Label>
                                <Input
                                    id="max_members"
                                    name="max_members"
                                    type="number"
                                    defaultValue={plan?.max_members ?? ''}
                                    min={1}
                                    placeholder="Kosong berarti tanpa batas"
                                />
                                <InputError message={errors.max_members} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="max_staff">Batas staf</Label>
                                <Input
                                    id="max_staff"
                                    name="max_staff"
                                    type="number"
                                    defaultValue={plan?.max_staff ?? ''}
                                    min={1}
                                    placeholder="Kosong berarti tanpa batas"
                                />
                                <InputError message={errors.max_staff} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="description">Deskripsi</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    defaultValue={plan?.description ?? ''}
                                    rows={4}
                                    maxLength={2000}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                <InputError message={errors.description} />
                            </div>
                            <div className="sm:col-span-2">
                                <div className="flex items-start gap-3 rounded-lg bg-muted/40 p-4">
                                    <Checkbox
                                        id="is_active_control"
                                        checked={isActive}
                                        onCheckedChange={(checked) =>
                                            setIsActive(checked === true)
                                        }
                                    />
                                    <div>
                                        <Label htmlFor="is_active_control">
                                            Tersedia untuk pendaftaran baru
                                        </Label>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Subscription lama tetap tersimpan
                                            jika paket dinonaktifkan.
                                        </p>
                                    </div>
                                </div>
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value={isActive ? '1' : '0'}
                                />
                                <InputError message={errors.is_active} />
                            </div>
                        </div>
                    </section>
                    <div className="flex flex-col-reverse gap-2 border-t pt-5 sm:flex-row sm:justify-end">
                        <Button variant="outline" asChild>
                            <Link href={cancelHref}>Batal</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save />
                            {processing ? 'Menyimpan...' : submitLabel}
                        </Button>
                    </div>
                </div>
            )}
        </Form>
    );
}
