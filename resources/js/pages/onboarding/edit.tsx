import { Form, Head, Link } from '@inertiajs/react';
import { ArrowRight, Check, LogOut } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { update } from '@/actions/App/Http/Controllers/OnboardingController';
import AppLogoIcon from '@/components/app-logo-icon';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import type { SelectOption } from '@/types';

type Props = {
    gym: {
        id: number;
        name: string;
        timezone: string;
        phone: string | null;
        email: string | null;
        address: string | null;
    };
    timezoneOptions: SelectOption[];
};

const fieldClassName = 'h-11 bg-background shadow-none';

function OnboardingErrors({
    errors,
    processing,
}: {
    errors: Record<string, string>;
    processing: boolean;
}) {
    const errorSummaryRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!processing && Object.keys(errors).length > 0) {
            errorSummaryRef.current?.focus();
        }
    }, [errors, processing]);

    if (Object.keys(errors).length === 0) {
        return null;
    }

    return (
        <div
            ref={errorSummaryRef}
            role="alert"
            tabIndex={-1}
            className="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            <p className="font-medium">Periksa kembali data Anda</p>
            <ul className="mt-2 grid list-inside list-disc gap-1 text-muted-foreground">
                {Object.entries(errors).map(([field, message]) => (
                    <li key={field}>
                        <a
                            href={`#${field}`}
                            onClick={(event) => {
                                event.preventDefault();
                                document.getElementById(field)?.focus();
                            }}
                            className="underline underline-offset-4 hover:text-foreground"
                        >
                            {message}
                        </a>
                    </li>
                ))}
            </ul>
        </div>
    );
}

export default function OnboardingEdit({ gym, timezoneOptions }: Props) {
    const availableTimezones = timezoneOptions.some(
        (option) => option.value === gym.timezone,
    )
        ? timezoneOptions
        : [...timezoneOptions, { value: gym.timezone, label: gym.timezone }];

    return (
        <div className="min-h-dvh bg-background text-foreground">
            <Head title="Siapkan gym Anda" />
            <header className="border-b">
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-5 sm:px-8">
                    <div className="flex items-center gap-2.5 font-semibold tracking-tight">
                        <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-4.5" aria-hidden />
                        </span>
                        Gymlo
                    </div>
                    <Button
                        asChild
                        variant="ghost"
                        className="h-10 text-muted-foreground"
                    >
                        <Link href={logout()} as="button">
                            <LogOut className="size-4" aria-hidden />
                            Keluar
                        </Link>
                    </Button>
                </div>
            </header>

            <main className="mx-auto grid w-full max-w-6xl gap-8 px-5 py-8 sm:px-8 sm:py-12 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:gap-16 lg:py-16">
                <div className="flex flex-col gap-6 lg:gap-8">
                    <nav aria-label="Progres pendaftaran">
                        <ol className="flex flex-wrap items-center gap-3 text-xs font-medium sm:text-sm">
                            <li className="flex items-center gap-2 text-muted-foreground">
                                <span className="flex size-6 items-center justify-center rounded-full bg-primary/10 text-primary">
                                    <Check className="size-3.5" aria-hidden />
                                </span>
                                Akun dibuat
                            </li>
                            <li aria-hidden className="h-px w-6 bg-border" />
                            <li
                                aria-current="step"
                                className="flex items-center gap-2"
                            >
                                <span className="flex size-6 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">
                                    2
                                </span>
                                Profil gym
                            </li>
                        </ol>
                    </nav>
                    <div>
                        <p className="text-xs font-medium tracking-widest text-muted-foreground uppercase">
                            Satu langkah lagi
                        </p>
                        <h1 className="mt-3 text-3xl leading-tight font-semibold tracking-tight text-balance sm:text-4xl">
                            Siapkan gym Anda.
                        </h1>
                        <p className="mt-4 max-w-sm text-sm leading-6 text-pretty text-muted-foreground">
                            Cek nama gym dan zona waktu. Kontak dapat dilengkapi
                            sekarang atau nanti melalui Pengaturan Gym.
                        </p>
                    </div>
                    <div className="hidden max-w-sm border-t pt-6 lg:block">
                        <p className="text-sm font-medium">Setelah ini</p>
                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                            Anda dapat membuat paket membership, menambahkan
                            member pertama, dan mulai mencatat pembayaran.
                        </p>
                    </div>
                </div>

                <Form
                    key={gym.id}
                    {...update.form()}
                    disableWhileProcessing
                    options={{ preserveScroll: 'errors' }}
                    className="min-w-0 rounded-xl border bg-card"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="flex flex-col gap-6 p-5 sm:p-7">
                                <OnboardingErrors
                                    errors={errors}
                                    processing={processing}
                                />

                                <section
                                    aria-labelledby="gym-profile-title"
                                    className="grid gap-5"
                                >
                                    <h2
                                        id="gym-profile-title"
                                        className="text-base font-semibold"
                                    >
                                        Profil gym
                                    </h2>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Nama gym</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={gym.name}
                                            autoComplete="organization"
                                            maxLength={120}
                                            required
                                            className={fieldClassName}
                                            aria-invalid={Boolean(errors.name)}
                                            aria-describedby={
                                                errors.name
                                                    ? 'name-error'
                                                    : undefined
                                            }
                                        />
                                        <InputError
                                            id="name-error"
                                            message={errors.name}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="timezone">
                                            Zona waktu gym
                                        </Label>
                                        <select
                                            id="timezone"
                                            name="timezone"
                                            defaultValue={gym.timezone}
                                            required
                                            aria-invalid={Boolean(
                                                errors.timezone,
                                            )}
                                            aria-describedby={`timezone-hint${errors.timezone ? ' timezone-error' : ''}`}
                                            className="h-11 w-full min-w-0 rounded-md border border-input bg-background px-3 text-base outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive md:text-sm"
                                        >
                                            {availableTimezones.map(
                                                (option) => (
                                                    <option
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </option>
                                                ),
                                            )}
                                        </select>
                                        <p
                                            id="timezone-hint"
                                            className="text-xs leading-5 text-muted-foreground"
                                        >
                                            Digunakan untuk jadwal, check-in,
                                            dan laporan harian.
                                        </p>
                                        <InputError
                                            id="timezone-error"
                                            message={errors.timezone}
                                        />
                                    </div>
                                </section>

                                <section
                                    aria-labelledby="gym-contact-title"
                                    className="grid gap-5 border-t pt-6"
                                >
                                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <h2
                                            id="gym-contact-title"
                                            className="text-base font-semibold"
                                        >
                                            Kontak gym
                                        </h2>
                                        <span className="text-xs text-muted-foreground">
                                            Opsional · Bisa diisi nanti
                                        </span>
                                    </div>
                                    <div className="grid gap-5 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="phone">
                                                Nomor telepon
                                            </Label>
                                            <Input
                                                id="phone"
                                                name="phone"
                                                type="tel"
                                                autoComplete="tel"
                                                maxLength={30}
                                                placeholder="08xxxxxxxxxx"
                                                defaultValue={gym.phone ?? ''}
                                                className={fieldClassName}
                                                aria-invalid={Boolean(
                                                    errors.phone,
                                                )}
                                                aria-describedby={
                                                    errors.phone
                                                        ? 'phone-error'
                                                        : undefined
                                                }
                                            />
                                            <InputError
                                                id="phone-error"
                                                message={errors.phone}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="email">
                                                Email gym
                                            </Label>
                                            <Input
                                                id="email"
                                                name="email"
                                                type="email"
                                                autoComplete="email"
                                                maxLength={255}
                                                placeholder="halo@gymanda.id"
                                                defaultValue={gym.email ?? ''}
                                                className={fieldClassName}
                                                aria-invalid={Boolean(
                                                    errors.email,
                                                )}
                                                aria-describedby={
                                                    errors.email
                                                        ? 'email-error'
                                                        : undefined
                                                }
                                            />
                                            <InputError
                                                id="email-error"
                                                message={errors.email}
                                            />
                                        </div>
                                        <div className="grid gap-2 sm:col-span-2">
                                            <Label htmlFor="address">
                                                Alamat gym
                                            </Label>
                                            <textarea
                                                id="address"
                                                name="address"
                                                autoComplete="street-address"
                                                maxLength={1000}
                                                placeholder="Nama jalan, nomor, dan kota"
                                                defaultValue={gym.address ?? ''}
                                                rows={2}
                                                aria-invalid={Boolean(
                                                    errors.address,
                                                )}
                                                aria-describedby={
                                                    errors.address
                                                        ? 'address-error'
                                                        : undefined
                                                }
                                                className="min-h-22 w-full resize-y rounded-md border border-input bg-background px-3 py-2.5 text-base outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive md:text-sm"
                                            />
                                            <InputError
                                                id="address-error"
                                                message={errors.address}
                                            />
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <div className="border-t px-5 py-5 sm:px-7">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="h-11 w-full shadow-none"
                                    data-test="complete-onboarding-button"
                                >
                                    {processing && <Spinner />}
                                    {processing
                                        ? 'Menyiapkan gym...'
                                        : 'Simpan & mulai kelola gym'}
                                    {!processing && (
                                        <ArrowRight
                                            className="size-4"
                                            aria-hidden
                                        />
                                    )}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </main>
        </div>
    );
}
