import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatCurrency } from '@/lib/formatters';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
    saasPlans: Array<{
        id: number;
        name: string;
        description: string | null;
        price: string;
        currency: string;
        billing_interval_label: string;
        trial_days: number;
        max_members: number | null;
        max_staff: number | null;
    }>;
};

export default function Register({ passwordRules, saasPlans }: Props) {
    return (
        <>
            <Head title="Daftar" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama lengkap</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Nama lengkap"
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="gym_name">Nama gym</Label>
                                <Input
                                    id="gym_name"
                                    type="text"
                                    required
                                    tabIndex={2}
                                    autoComplete="organization"
                                    name="gym_name"
                                    placeholder="Contoh: Gym Sehat Jakarta"
                                />
                                <InputError message={errors.gym_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Alamat email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={3}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <fieldset className="grid gap-3">
                                <legend className="text-sm font-medium">
                                    Pilih paket SaaS
                                </legend>
                                {saasPlans.length === 0 ? (
                                    <p className="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">
                                        Belum ada paket pendaftaran aktif.
                                        Hubungi administrator GymFlow.
                                    </p>
                                ) : (
                                    <div className="grid gap-2">
                                        {saasPlans.map((plan, index) => (
                                            <label
                                                key={plan.id}
                                                className="flex cursor-pointer items-start gap-3 rounded-lg border p-3 has-checked:border-primary has-checked:bg-primary/5"
                                            >
                                                <input
                                                    type="radio"
                                                    name="saas_plan_id"
                                                    value={plan.id}
                                                    defaultChecked={index === 0}
                                                    required
                                                    className="mt-1"
                                                />
                                                <span className="min-w-0 flex-1">
                                                    <span className="flex flex-wrap items-baseline justify-between gap-2 text-sm font-medium">
                                                        <span>{plan.name}</span>
                                                        <span>
                                                            {formatCurrency(
                                                                plan.price,
                                                                plan.currency,
                                                            )}{' '}
                                                            /{' '}
                                                            {plan.billing_interval_label.toLowerCase()}
                                                        </span>
                                                    </span>
                                                    <span className="mt-1 block text-xs leading-5 text-muted-foreground">
                                                        {plan.trial_days > 0
                                                            ? `Uji coba ${plan.trial_days} hari. `
                                                            : ''}
                                                        {plan.description}
                                                    </span>
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                )}
                                <InputError message={errors.saas_plan_id} />
                            </fieldset>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Kata sandi</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Kata sandi"
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
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
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={6}
                                data-test="register-user-button"
                                disabled={saasPlans.length === 0}
                            >
                                {processing && <Spinner />}
                                Buat workspace
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Sudah memiliki akun?{' '}
                            <TextLink href={login()} tabIndex={7}>
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
    title: 'Buat workspace gym',
    description: 'Akun pertama otomatis menjadi Owner gym.',
};
