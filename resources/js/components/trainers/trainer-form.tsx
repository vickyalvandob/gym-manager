import { Form, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SelectOption, Trainer, TrainerAccountOption } from '@/types';

type TrainerFormProps = {
    form: {
        action: string;
        method: 'post';
    };
    trainer?: Trainer;
    statusOptions: SelectOption[];
    accountOptions: TrainerAccountOption[];
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    submitLabel: string;
};

const selectClassName =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export function TrainerForm({
    form,
    trainer,
    statusOptions,
    accountOptions,
    cancelHref,
    submitLabel,
}: TrainerFormProps) {
    const [selectedUserId, setSelectedUserId] = useState(
        trainer?.user_id?.toString() ?? '',
    );
    const createsLoginAccount = !trainer && selectedUserId === '';

    return (
        <Form {...form} options={{ preserveScroll: true }}>
            {({ errors, processing }) => (
                <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_19rem]">
                    <div className="min-w-0 divide-y border-y">
                        <section className="grid gap-5 py-6 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <h2 className="text-base font-semibold">
                                    Informasi trainer
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Data kontak dan keahlian yang digunakan oleh
                                    tim operasional.
                                </p>
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Nama trainer</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={trainer?.name ?? ''}
                                    required
                                    maxLength={120}
                                    autoFocus
                                    placeholder="Contoh: Raka Pratama"
                                />
                                <InputError message={errors.name} />
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

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Nomor telepon</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    defaultValue={trainer?.phone ?? ''}
                                    required
                                    maxLength={30}
                                    placeholder="0812 8888 0001"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={trainer?.email ?? ''}
                                    maxLength={255}
                                    placeholder="trainer@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
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
                        </section>

                        <section className="grid gap-5 py-6">
                            <div>
                                <h2 className="text-base font-semibold">
                                    Catatan internal
                                </h2>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="bio">Bio profesional</Label>
                                <textarea
                                    id="bio"
                                    name="bio"
                                    defaultValue={trainer?.bio ?? ''}
                                    maxLength={2000}
                                    rows={4}
                                    className="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Pengalaman, sertifikasi, atau pendekatan latihan."
                                />
                                <InputError message={errors.bio} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="notes">Catatan</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    defaultValue={trainer?.notes ?? ''}
                                    maxLength={2000}
                                    rows={5}
                                    className="min-h-32 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                    placeholder="Informasi operasional yang perlu diketahui owner atau front desk."
                                />
                                <InputError message={errors.notes} />
                            </div>
                        </section>
                    </div>

                    <aside className="space-y-6">
                        <div className="grid gap-5 border-y py-5">
                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
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

                            <div className="grid gap-2">
                                <Label htmlFor="user_id">
                                    Akun login (opsional)
                                </Label>
                                <select
                                    id="user_id"
                                    name="user_id"
                                    value={selectedUserId}
                                    onChange={(event) =>
                                        setSelectedUserId(event.target.value)
                                    }
                                    className={selectClassName}
                                >
                                    <option value="">
                                        {trainer
                                            ? 'Tanpa akun login'
                                            : 'Buat akun login baru'}
                                    </option>
                                    {accountOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label} —{' '}
                                            {option.description}
                                        </option>
                                    ))}
                                </select>
                                <p className="text-xs leading-5 text-muted-foreground">
                                    {createsLoginAccount
                                        ? 'Akun Trainer baru dibuat menggunakan email dan kata sandi di bawah.'
                                        : 'Hubungkan akun berperan Trainer agar workspace sesuai assignment.'}
                                </p>
                                <InputError message={errors.user_id} />
                            </div>

                            {createsLoginAccount && (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="password">
                                            Kata sandi akun
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
                                            Konfirmasi kata sandi
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
                                </>
                            )}
                        </div>

                        <div className="flex gap-2">
                            <Button
                                type="submit"
                                disabled={processing}
                                className="flex-1"
                                data-test="save-trainer"
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
