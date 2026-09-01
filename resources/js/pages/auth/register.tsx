import { Form, Head } from '@inertiajs/react';
import { ArrowRight, CheckCircle2 } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
};

export default function Register({ passwordRules }: Props) {
    return (
        <>
            <Head title="Daftar" />

            <div className="mb-6 flex items-start gap-3 border-l-2 border-primary bg-primary/5 px-4 py-3.5">
                <CheckCircle2
                    className="mt-0.5 size-4 shrink-0 text-primary"
                    aria-hidden
                />
                <div>
                    <p className="text-sm font-medium">
                        Mulai langsung dengan Free
                    </p>
                    <p className="mt-0.5 text-xs leading-5 text-muted-foreground">
                        Mulai dengan 1 gym, hingga 20 member dan 5 staff.
                    </p>
                </div>
            </div>

            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="name"
                                    className="text-sm font-medium"
                                >
                                    Nama lengkap
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Contoh: Andi Saputra"
                                    className="h-12 rounded-md bg-transparent px-4 shadow-none"
                                    aria-invalid={Boolean(errors.name)}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="gym_name"
                                    className="text-sm font-medium"
                                >
                                    Nama gym
                                </Label>
                                <Input
                                    id="gym_name"
                                    type="text"
                                    required
                                    tabIndex={2}
                                    autoComplete="organization"
                                    name="gym_name"
                                    placeholder="Contoh: Gym Sehat Jakarta"
                                    className="h-12 rounded-md bg-transparent px-4 shadow-none"
                                    aria-invalid={Boolean(errors.gym_name)}
                                />
                                <InputError message={errors.gym_name} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label
                                    htmlFor="email"
                                    className="text-sm font-medium"
                                >
                                    Alamat email
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={3}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                    className="h-12 rounded-md bg-transparent px-4 shadow-none"
                                    aria-invalid={Boolean(errors.email)}
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="password"
                                    className="text-sm font-medium"
                                >
                                    Kata sandi
                                </Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Buat kata sandi"
                                    passwordrules={passwordRules}
                                    className="h-12 rounded-md bg-transparent px-4 shadow-none"
                                    aria-invalid={Boolean(errors.password)}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="password_confirmation"
                                    className="text-sm font-medium"
                                >
                                    Konfirmasi kata sandi
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={5}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Ulangi kata sandi"
                                    passwordrules={passwordRules}
                                    className="h-12 rounded-md bg-transparent px-4 shadow-none"
                                    aria-invalid={Boolean(
                                        errors.password_confirmation,
                                    )}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                size="lg"
                                className="mt-1 h-12 w-full rounded-md shadow-none sm:col-span-2"
                                tabIndex={6}
                                disabled={processing}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                <span>Buat workspace gratis</span>
                                {!processing && (
                                    <ArrowRight
                                        className="size-4"
                                        aria-hidden
                                    />
                                )}
                            </Button>
                        </div>

                        <div className="border-t pt-6 text-center text-sm text-muted-foreground">
                            Sudah memiliki akun?{' '}
                            <TextLink
                                href={login()}
                                tabIndex={7}
                                className="font-semibold text-primary no-underline hover:underline"
                            >
                                Masuk
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Bangun workspace gym Anda',
    description:
        'Satu langkah untuk membuat akun Owner dan gym pertama yang siap digunakan.',
};
