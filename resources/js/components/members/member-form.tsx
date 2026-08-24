import { Form, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { Save, Upload } from 'lucide-react';
import InputError from '@/components/input-error';
import { MemberAvatar } from '@/components/members/member-avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { MemberDetail, SelectOption } from '@/types';

type MemberFormProps = {
    form: {
        action: string;
        method: 'post';
    };
    member?: MemberDetail;
    genderOptions: SelectOption[];
    statusOptions: SelectOption[];
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    submitLabel: string;
};

const selectClassName =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

export function MemberForm({
    form,
    member,
    genderOptions,
    statusOptions,
    cancelHref,
    submitLabel,
}: MemberFormProps) {
    return (
        <Form {...form} options={{ preserveScroll: true }}>
            {({ errors, processing, progress }) => (
                <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <div className="min-w-0 divide-y border-y">
                        <section className="grid gap-5 py-6 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <h2 className="text-base font-semibold">
                                    Identitas member
                                </h2>
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Nama lengkap</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={member?.name ?? ''}
                                    required
                                    maxLength={120}
                                    autoComplete="name"
                                    autoFocus
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Nomor telepon</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    defaultValue={member?.phone ?? ''}
                                    required
                                    maxLength={30}
                                    inputMode="tel"
                                    autoComplete="tel"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    defaultValue={member?.email ?? ''}
                                    maxLength={255}
                                    autoComplete="email"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="gender">Jenis kelamin</Label>
                                <select
                                    id="gender"
                                    name="gender"
                                    defaultValue={member?.gender ?? ''}
                                    className={selectClassName}
                                >
                                    <option value="">Tidak diisi</option>
                                    {genderOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.gender} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="birth_date">
                                    Tanggal lahir
                                </Label>
                                <Input
                                    id="birth_date"
                                    type="date"
                                    name="birth_date"
                                    defaultValue={member?.birth_date ?? ''}
                                    max={new Date().toISOString().slice(0, 10)}
                                />
                                <InputError message={errors.birth_date} />
                            </div>
                        </section>

                        <section className="grid gap-5 py-6 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <h2 className="text-base font-semibold">
                                    Kontak dan catatan
                                </h2>
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="address">Alamat</Label>
                                <textarea
                                    id="address"
                                    name="address"
                                    defaultValue={member?.address ?? ''}
                                    maxLength={1000}
                                    rows={3}
                                    className="min-h-24 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                />
                                <InputError message={errors.address} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="emergency_contact">
                                    Kontak darurat
                                </Label>
                                <Input
                                    id="emergency_contact"
                                    name="emergency_contact"
                                    defaultValue={
                                        member?.emergency_contact ?? ''
                                    }
                                    maxLength={120}
                                />
                                <InputError
                                    message={errors.emergency_contact}
                                />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="notes">Catatan</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    defaultValue={member?.notes ?? ''}
                                    maxLength={2000}
                                    rows={4}
                                    className="min-h-28 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                />
                                <InputError message={errors.notes} />
                            </div>
                        </section>
                    </div>

                    <aside className="space-y-6">
                        <div className="grid gap-3">
                            <Label htmlFor="photo">Foto member</Label>
                            {member && (
                                <MemberAvatar
                                    name={member.name}
                                    photoUrl={member.photo_url}
                                    className="size-20 rounded-lg"
                                />
                            )}
                            <label
                                htmlFor="photo"
                                className="flex h-9 cursor-pointer items-center justify-center gap-2 rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent"
                            >
                                <Upload className="size-4" />
                                Pilih foto
                            </label>
                            <input
                                id="photo"
                                name="photo"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                className="sr-only"
                            />
                            <InputError message={errors.photo} />
                        </div>

                        <div className="grid gap-2 border-t pt-6">
                            <Label htmlFor="status">Status</Label>
                            <select
                                id="status"
                                name="status"
                                defaultValue={member?.status ?? 'active'}
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

                        {progress && (
                            <div
                                className="h-1.5 overflow-hidden rounded-full bg-muted"
                                aria-label={`Unggahan ${progress.percentage}%`}
                            >
                                <div
                                    className="h-full bg-primary transition-[width]"
                                    style={{ width: `${progress.percentage}%` }}
                                />
                            </div>
                        )}

                        <div className="flex gap-2 border-t pt-6">
                            <Button
                                type="submit"
                                disabled={processing}
                                className="flex-1"
                                data-test="save-member"
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
