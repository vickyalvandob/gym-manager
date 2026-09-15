import { Form, Head } from '@inertiajs/react';
import {
    KeyRound,
    Mail,
    MapPin,
    Phone,
    ShieldCheck,
    UserRound,
} from 'lucide-react';
import type { ReactNode } from 'react';
import MemberPortalController from '@/actions/App/Http/Controllers/MemberPortalController';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/input-error';
import { PortalPageHeader } from '@/components/member-portal/portal-page-header';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { MemberPortalSettingsProps } from '@/types';

export default function MemberPortalSettings({
    member,
    passwordRules,
}: MemberPortalSettingsProps) {
    return (
        <div className="mx-auto max-w-5xl space-y-7">
            <Head title="Profil & Keamanan" />
            <PortalPageHeader
                eyebrow="Akun"
                title="Profil & keamanan"
                description="Perbarui kontak dasar dan jaga keamanan akun portal member Anda."
            />

            <div className="grid gap-6 xl:grid-cols-2 xl:items-start">
                <section className="rounded-xl border bg-background p-5 sm:p-6">
                    <div className="flex items-start gap-3 border-b pb-5">
                        <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <UserRound className="size-4" />
                        </span>
                        <div>
                            <h2 className="font-semibold">Kontak dasar</h2>
                            <p className="mt-0.5 text-xs leading-5 text-muted-foreground">
                                Email digunakan untuk login dan pemulihan akun.
                            </p>
                        </div>
                    </div>

                    <Form
                        {...MemberPortalController.update.form(member.id)}
                        options={{ preserveScroll: true }}
                        resetOnSuccess={['current_password']}
                        className="mt-5 grid gap-4"
                    >
                        {({ errors, processing }) => (
                            <>
                                <Field
                                    icon={<Phone />}
                                    label="Nomor telepon"
                                    htmlFor="phone"
                                >
                                    <Input
                                        id="phone"
                                        name="phone"
                                        defaultValue={member.phone}
                                        autoComplete="tel"
                                    />
                                    <InputError message={errors.phone} />
                                </Field>
                                <Field
                                    icon={<Mail />}
                                    label="Email login"
                                    htmlFor="email"
                                >
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={member.email}
                                        autoComplete="email"
                                    />
                                    <InputError message={errors.email} />
                                </Field>
                                <Field
                                    icon={<MapPin />}
                                    label="Alamat"
                                    htmlFor="address"
                                >
                                    <textarea
                                        id="address"
                                        name="address"
                                        defaultValue={member.address ?? ''}
                                        rows={4}
                                        className="min-h-24 w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    />
                                    <InputError message={errors.address} />
                                </Field>
                                <Field
                                    icon={<Phone />}
                                    label="Kontak darurat"
                                    htmlFor="emergency_contact"
                                >
                                    <Input
                                        id="emergency_contact"
                                        name="emergency_contact"
                                        defaultValue={
                                            member.emergency_contact ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.emergency_contact}
                                    />
                                </Field>
                                <Field
                                    icon={<KeyRound />}
                                    label="Kata sandi saat ini"
                                    htmlFor="contact_current_password"
                                >
                                    <PasswordInput
                                        id="contact_current_password"
                                        name="current_password"
                                        autoComplete="current-password"
                                        placeholder="Wajib jika mengganti email"
                                    />
                                    <InputError
                                        message={errors.current_password}
                                    />
                                </Field>
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Menyimpan...'
                                        : 'Simpan Perubahan'}
                                </Button>
                            </>
                        )}
                    </Form>
                </section>

                <section className="rounded-xl border bg-background p-5 sm:p-6">
                    <div className="flex items-start gap-3 border-b pb-5">
                        <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <ShieldCheck className="size-4" />
                        </span>
                        <div>
                            <h2 className="font-semibold">Ganti kata sandi</h2>
                            <p className="mt-0.5 text-xs leading-5 text-muted-foreground">
                                Gunakan kata sandi panjang yang tidak dipakai di
                                layanan lain.
                            </p>
                        </div>
                    </div>

                    <Form
                        {...SecurityController.update.form()}
                        options={{ preserveScroll: true }}
                        resetOnError={[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]}
                        resetOnSuccess
                        className="mt-5 grid gap-4"
                    >
                        {({ errors, processing }) => (
                            <>
                                <Field
                                    label="Kata sandi saat ini"
                                    htmlFor="password_current"
                                >
                                    <PasswordInput
                                        id="password_current"
                                        name="current_password"
                                        autoComplete="current-password"
                                    />
                                    <InputError
                                        message={errors.current_password}
                                    />
                                </Field>
                                <Field
                                    label="Kata sandi baru"
                                    htmlFor="password"
                                >
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        autoComplete="new-password"
                                        passwordrules={passwordRules}
                                    />
                                    <InputError message={errors.password} />
                                </Field>
                                <Field
                                    label="Konfirmasi kata sandi"
                                    htmlFor="password_confirmation"
                                >
                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        autoComplete="new-password"
                                        passwordrules={passwordRules}
                                    />
                                    <InputError
                                        message={errors.password_confirmation}
                                    />
                                </Field>
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Memperbarui...'
                                        : 'Perbarui Kata Sandi'}
                                </Button>
                            </>
                        )}
                    </Form>
                </section>
            </div>
        </div>
    );
}

function Field({
    icon,
    label,
    htmlFor,
    children,
}: {
    icon?: ReactNode;
    label: string;
    htmlFor: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={htmlFor} className="flex items-center gap-2">
                {icon && (
                    <span className="text-muted-foreground [&_svg]:size-3.5">
                        {icon}
                    </span>
                )}
                {label}
            </Label>
            {children}
        </div>
    );
}
