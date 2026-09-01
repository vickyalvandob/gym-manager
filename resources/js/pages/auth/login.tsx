import { Form, Head } from '@inertiajs/react';
import { ArrowRight, CircleCheck } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Masuk" />

            {status && (
                <div className="mb-6 flex items-start gap-3 border border-primary/25 bg-primary/5 p-3.5 text-sm text-foreground">
                    <CircleCheck
                        className="mt-0.5 size-4 shrink-0 text-primary"
                        aria-hidden
                    />
                    <p className="leading-5">{status}</p>
                </div>
            )}

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                disableWhileProcessing
                className="flex max-w-lg flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-5">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="email"
                                    className="text-sm font-medium"
                                >
                                    Alamat email
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                    className="h-12 rounded-md bg-transparent px-4 shadow-none"
                                    aria-invalid={Boolean(errors.email)}
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Kata sandi</Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-xs font-medium text-primary no-underline hover:underline"
                                            tabIndex={3}
                                        >
                                            Lupa kata sandi?
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Masukkan kata sandi"
                                    className="h-12 rounded-md bg-transparent px-4 shadow-none"
                                    aria-invalid={Boolean(errors.password)}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center gap-3 py-1">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={4}
                                />
                                <Label htmlFor="remember">Ingat saya</Label>
                            </div>

                            <Button
                                type="submit"
                                size="lg"
                                className="mt-1 h-12 w-full rounded-md shadow-none"
                                tabIndex={5}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                <span>Masuk ke workspace</span>
                                {!processing && (
                                    <ArrowRight
                                        className="size-4"
                                        aria-hidden
                                    />
                                )}
                            </Button>
                        </div>

                        <div className="border-t pt-6 text-center text-sm text-muted-foreground">
                            Belum memiliki akun?{' '}
                            <TextLink
                                href={register()}
                                tabIndex={6}
                                className="font-semibold text-primary no-underline hover:underline"
                            >
                                Buat workspace gratis
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Login.layout = {
    title: 'Selamat datang kembali',
    description:
        'Masuk untuk melanjutkan pekerjaan Anda dari workspace yang terakhir digunakan.',
};
