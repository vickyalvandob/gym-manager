import { Form, Head } from '@inertiajs/react';
import { Building2, CheckCircle2, Rocket } from 'lucide-react';
import OnboardingController from '@/actions/App/Http/Controllers/OnboardingController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/formatters';
import { edit } from '@/routes/onboarding';
import type { SelectOption } from '@/types';

type Props = {
    gym: {
        name: string;
        timezone: string;
        currency: string;
        phone: string | null;
        email: string | null;
        address: string | null;
    };
    subscription: {
        plan_name: string;
        status_label: string;
        trial_ends_at: string | null;
    } | null;
    timezoneOptions: SelectOption[];
    currencyOptions: SelectOption[];
};

const selectClassName =
    'h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function OnboardingEdit({
    gym,
    subscription,
    timezoneOptions,
    currencyOptions,
}: Props) {
    return (
        <>
            <Head title="Siapkan Gym" />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="rounded-xl border bg-muted/20 p-5 sm:p-6">
                    <div className="flex items-start gap-4">
                        <div className="rounded-lg bg-primary p-2 text-primary-foreground">
                            <Rocket className="size-5" />
                        </div>
                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Langkah terakhir
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold">
                                Siapkan workspace gym
                            </h1>
                            <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                Lengkapi informasi dasar agar tanggal, mata
                                uang, dan kontak operasional konsisten.
                            </p>
                        </div>
                    </div>
                    {subscription && (
                        <div className="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 border-t pt-4 text-sm">
                            <span className="font-medium">
                                {subscription.plan_name}
                            </span>
                            <span className="text-muted-foreground">
                                {subscription.status_label}
                            </span>
                            {subscription.trial_ends_at && (
                                <span className="text-muted-foreground">
                                    Trial sampai{' '}
                                    {formatDate(subscription.trial_ends_at)}
                                </span>
                            )}
                        </div>
                    )}
                </div>

                <Form
                    {...OnboardingController.update.form()}
                    className="rounded-xl border p-5 sm:p-6"
                >
                    {({ errors, processing }) => (
                        <div className="grid gap-5">
                            <div className="flex items-center gap-3 border-b pb-4">
                                <Building2 className="size-5 text-muted-foreground" />
                                <div>
                                    <h2 className="font-semibold">
                                        Profil operasional
                                    </h2>
                                    <p className="text-xs text-muted-foreground">
                                        Informasi ini masih dapat diubah dari
                                        pengaturan gym.
                                    </p>
                                </div>
                            </div>
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="name">Nama gym *</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={gym.name}
                                        required
                                        autoFocus
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="timezone">
                                        Zona waktu *
                                    </Label>
                                    <select
                                        id="timezone"
                                        name="timezone"
                                        defaultValue={gym.timezone}
                                        className={selectClassName}
                                    >
                                        {timezoneOptions.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.timezone} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="currency">
                                        Mata uang *
                                    </Label>
                                    <select
                                        id="currency"
                                        name="currency"
                                        defaultValue={gym.currency}
                                        className={selectClassName}
                                    >
                                        {currencyOptions.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.currency} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Nomor telepon</Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        defaultValue={gym.phone ?? ''}
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email gym</Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={gym.email ?? ''}
                                    />
                                    <InputError message={errors.email} />
                                </div>
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="address">Alamat</Label>
                                    <textarea
                                        id="address"
                                        name="address"
                                        defaultValue={gym.address ?? ''}
                                        rows={3}
                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    />
                                    <InputError message={errors.address} />
                                </div>
                            </div>
                            <div className="flex justify-end border-t pt-5">
                                <Button type="submit" disabled={processing}>
                                    <CheckCircle2 />
                                    {processing
                                        ? 'Menyiapkan...'
                                        : 'Selesaikan onboarding'}
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}

OnboardingEdit.layout = {
    breadcrumbs: [{ title: 'Onboarding', href: edit() }],
};
