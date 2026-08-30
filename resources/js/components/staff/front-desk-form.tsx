import { Form, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { KeyRound, Save } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SelectOption, StaffMember } from '@/types';

type Props = {
    form: { action: string; method: 'post' };
    staffMember?: StaffMember;
    statusOptions: SelectOption[];
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    submitLabel: string;
};

const selectClassName =
    'h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export function FrontDeskForm({
    form,
    staffMember,
    statusOptions,
    cancelHref,
    submitLabel,
}: Props) {
    return (
        <Form
            {...form}
            options={{ preserveScroll: true }}
            className="w-full max-w-3xl"
        >
            {({ errors, processing }) => (
                <div className="grid gap-5">
                    <section className="rounded-lg border p-4 sm:p-6">
                        <h2 className="text-base font-semibold">
                            Akun Front Desk
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Akun ini hanya mendapat akses Front Desk pada gym
                            yang sedang aktif.
                        </p>
                        <div className="mt-5 grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Nama lengkap *</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={staffMember?.name ?? ''}
                                    required
                                    maxLength={255}
                                    autoComplete="name"
                                    autoFocus
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email login *</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={staffMember?.email ?? ''}
                                    required
                                    maxLength={255}
                                    autoComplete="email"
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="status">Status akses *</Label>
                                <select
                                    id="status"
                                    name="status"
                                    defaultValue={
                                        staffMember?.status ?? 'active'
                                    }
                                    className={selectClassName}
                                >
                                    {statusOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.status} />
                            </div>
                        </div>
                    </section>

                    <section className="rounded-lg border p-4 sm:p-6">
                        <div className="flex items-start gap-3">
                            <div className="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                <KeyRound className="size-4" />
                            </div>
                            <div>
                                <h2 className="text-base font-semibold">
                                    Kata sandi
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {staffMember
                                        ? 'Kosongkan jika kata sandi tidak ingin diubah.'
                                        : 'Bagikan kata sandi awal secara aman kepada staf.'}
                                </p>
                            </div>
                        </div>
                        <div className="mt-5 grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    Kata sandi {staffMember ? '' : '*'}
                                </Label>
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required={!staffMember}
                                    minLength={8}
                                    autoComplete="new-password"
                                />
                                <InputError message={errors.password} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Ulangi kata sandi {staffMember ? '' : '*'}
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    required={!staffMember}
                                    minLength={8}
                                    autoComplete="new-password"
                                />
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
