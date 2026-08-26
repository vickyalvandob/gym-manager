import { Form, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { KeyRound, Save } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SelectOption, Trainer } from '@/types';

type TrainerFormProps = {
    form: {
        action: string;
        method: 'post';
    };
    trainer?: Trainer;
    statusOptions: SelectOption[];
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    submitLabel: string;
};

const selectClassName =
    'h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export function TrainerForm({
    form,
    trainer,
    statusOptions,
    cancelHref,
    submitLabel,
}: TrainerFormProps) {
    return (
        <Form
            {...form}
            options={{ preserveScroll: true }}
            className="w-full max-w-3xl"
        >
            {({ errors, processing }) => (
                <div className="grid gap-5">
                    <section className="rounded-lg border p-4 sm:p-6">
                        <h2 className="text-base font-semibold">Data PT</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Data utama untuk operasional dan akun login PT.
                        </p>

                        <div className="mt-5 grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Nama PT *</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={trainer?.name ?? ''}
                                    required
                                    maxLength={120}
                                    autoFocus
                                    autoComplete="name"
                                    placeholder="Contoh: Raka Pratama"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email login *</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={trainer?.email ?? ''}
                                    required
                                    maxLength={255}
                                    autoComplete="email"
                                    placeholder="trainer@example.com"
                                />
                                <p className="text-xs leading-5 text-muted-foreground">
                                    Dipakai PT untuk masuk ke dashboard.
                                </p>
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Nomor telepon *</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    defaultValue={trainer?.phone ?? ''}
                                    required
                                    maxLength={30}
                                    autoComplete="tel"
                                    placeholder="0812 8888 0001"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="specialization">
                                    Spesialisasi
                                </Label>
                                <Input
                                    id="specialization"
                                    name="specialization"
                                    defaultValue={trainer?.specialization ?? ''}
                                    maxLength={160}
                                    placeholder="Contoh: Strength & Conditioning"
                                />
                                <InputError message={errors.specialization} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="joined_at">
                                    Tanggal bergabung
                                </Label>
                                <Input
                                    id="joined_at"
                                    name="joined_at"
                                    type="date"
                                    defaultValue={trainer?.joined_at ?? ''}
                                />
                                <InputError message={errors.joined_at} />
                            </div>

                            <div className="grid gap-2 sm:max-w-xs">
                                <Label htmlFor="status">Status *</Label>
                                <select
                                    id="status"
                                    name="status"
                                    defaultValue={trainer?.status ?? 'active'}
                                    className={selectClassName}
                                    required
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

                    {!trainer && (
                        <section className="rounded-lg border p-4 sm:p-6">
                            <div className="flex items-start gap-3">
                                <div className="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                    <KeyRound className="size-4" />
                                </div>
                                <div>
                                    <h2 className="text-base font-semibold">
                                        Kata sandi login
                                    </h2>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Akun PT dibuat otomatis saat data
                                        disimpan.
                                    </p>
                                </div>
                            </div>

                            <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="password">
                                        Kata sandi *
                                    </Label>
                                    <Input
                                        id="password"
                                        name="password"
                                        type="password"
                                        minLength={8}
                                        required
                                        autoComplete="new-password"
                                    />
                                    <InputError message={errors.password} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Ulangi kata sandi *
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        type="password"
                                        minLength={8}
                                        required
                                        autoComplete="new-password"
                                    />
                                </div>
                            </div>
                        </section>
                    )}

                    <details
                        className="rounded-lg border p-4 sm:p-6"
                        open={Boolean(
                            errors.bio ||
                            errors.notes ||
                            trainer?.bio ||
                            trainer?.notes,
                        )}
                    >
                        <summary className="cursor-pointer text-sm font-medium">
                            Informasi tambahan
                        </summary>
                        <div className="mt-5 grid gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="bio">Bio profesional</Label>
                                <textarea
                                    id="bio"
                                    name="bio"
                                    defaultValue={trainer?.bio ?? ''}
                                    maxLength={5000}
                                    rows={3}
                                    className="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                    placeholder="Pengalaman atau sertifikasi utama."
                                />
                                <InputError message={errors.bio} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="notes">Catatan internal</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    defaultValue={trainer?.notes ?? ''}
                                    maxLength={2000}
                                    rows={3}
                                    className="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                    placeholder="Catatan untuk Owner atau Front Desk."
                                />
                                <InputError message={errors.notes} />
                            </div>
                        </div>
                    </details>

                    <div className="flex flex-col-reverse gap-2 border-t pt-5 sm:flex-row sm:justify-end">
                        <Button variant="outline" asChild>
                            <Link href={cancelHref}>Batal</Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="save-trainer"
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
