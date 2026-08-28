import { Form, Head, router } from '@inertiajs/react';
import { Building2, Save, Trash2, Upload } from 'lucide-react';
import { useEffect, useState } from 'react';
import GymSettingsController from '@/actions/App/Http/Controllers/GymSettingsController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/gym-settings';

type GymSettings = {
    id: number;
    name: string;
    logo_url: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    membership_expiry_warning_days: number;
};

type GymSettingsPageProps = {
    gym: GymSettings;
};

export default function EditGymSettings({ gym }: GymSettingsPageProps) {
    const [selectedLogoUrl, setSelectedLogoUrl] = useState<string | null>(null);
    const [removingLogo, setRemovingLogo] = useState(false);

    useEffect(() => {
        return () => {
            if (selectedLogoUrl) {
                URL.revokeObjectURL(selectedLogoUrl);
            }
        };
    }, [selectedLogoUrl]);

    function selectLogo(file: File | undefined) {
        setSelectedLogoUrl((currentUrl) => {
            if (currentUrl) {
                URL.revokeObjectURL(currentUrl);
            }

            return file ? URL.createObjectURL(file) : null;
        });
    }

    function removeLogo() {
        if (!window.confirm('Hapus logo gym saat ini?')) {
            return;
        }

        router.delete(GymSettingsController.destroyLogo(), {
            preserveScroll: true,
            onStart: () => setRemovingLogo(true),
            onFinish: () => setRemovingLogo(false),
        });
    }

    const logoUrl = selectedLogoUrl ?? gym.logo_url;

    return (
        <>
            <Head title="Pengaturan Gym" />

            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header>
                    <p className="text-sm font-medium text-primary">
                        Pengaturan aplikasi
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-normal">
                        Profil dan operasional gym
                    </h1>
                    <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
                        Atur profil, kontak, logo, dan peringatan masa aktif
                        membership.
                    </p>
                </header>

                <Form
                    {...GymSettingsController.update.form.patch()}
                    options={{ preserveScroll: true }}
                    className="min-w-0"
                >
                    {({ errors, processing, progress, recentlySuccessful }) => (
                        <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
                            <div className="min-w-0 divide-y border-y">
                                <section className="grid gap-5 py-6 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <h2 className="text-base font-semibold">
                                            Profil gym
                                        </h2>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            Informasi ini menjadi identitas
                                            utama gym di aplikasi.
                                        </p>
                                    </div>

                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label htmlFor="name">Nama gym</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={gym.name}
                                            required
                                            maxLength={120}
                                            autoFocus
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                </section>

                                <section className="grid gap-5 py-6 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <h2 className="text-base font-semibold">
                                            Kontak
                                        </h2>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            Kontak resmi yang digunakan untuk
                                            komunikasi operasional.
                                        </p>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">
                                            Nomor telepon
                                        </Label>
                                        <Input
                                            id="phone"
                                            name="phone"
                                            defaultValue={gym.phone ?? ''}
                                            maxLength={30}
                                            inputMode="tel"
                                            autoComplete="tel"
                                            placeholder="Contoh: +62 812 3456 7890"
                                        />
                                        <InputError message={errors.phone} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            name="email"
                                            defaultValue={gym.email ?? ''}
                                            maxLength={255}
                                            autoComplete="email"
                                            placeholder="halo@gymanda.com"
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label htmlFor="address">Alamat</Label>
                                        <textarea
                                            id="address"
                                            name="address"
                                            defaultValue={gym.address ?? ''}
                                            maxLength={1000}
                                            rows={3}
                                            className="min-h-24 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                        />
                                        <InputError message={errors.address} />
                                    </div>
                                </section>

                                <section className="grid gap-5 py-6 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <h2 className="text-base font-semibold">
                                            Peringatan membership
                                        </h2>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            Atur kapan membership yang akan
                                            berakhir mulai ditandai.
                                        </p>
                                    </div>

                                    <div className="grid gap-2 sm:col-span-2 sm:max-w-xs">
                                        <Label htmlFor="membership_expiry_warning_days">
                                            Peringatan membership berakhir
                                        </Label>
                                        <div className="flex items-center gap-2">
                                            <Input
                                                id="membership_expiry_warning_days"
                                                type="number"
                                                name="membership_expiry_warning_days"
                                                defaultValue={
                                                    gym.membership_expiry_warning_days
                                                }
                                                min={1}
                                                max={90}
                                                required
                                            />
                                            <span className="text-sm text-muted-foreground">
                                                hari
                                            </span>
                                        </div>
                                        <InputError
                                            message={
                                                errors.membership_expiry_warning_days
                                            }
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Member ditandai akan berakhir dalam
                                            rentang ini.
                                        </p>
                                    </div>
                                </section>
                            </div>

                            <aside className="space-y-6">
                                <div className="grid gap-3">
                                    <Label htmlFor="logo">Logo gym</Label>
                                    <div className="flex size-24 items-center justify-center overflow-hidden rounded-lg border bg-muted/30">
                                        {logoUrl ? (
                                            <img
                                                src={logoUrl}
                                                alt={`Logo ${gym.name}`}
                                                className="size-full object-contain"
                                            />
                                        ) : (
                                            <Building2 className="size-8 text-muted-foreground" />
                                        )}
                                    </div>

                                    <label
                                        htmlFor="logo"
                                        className="flex h-9 cursor-pointer items-center justify-center gap-2 rounded-md border border-input bg-background px-3 text-sm font-medium hover:bg-accent"
                                    >
                                        <Upload className="size-4" />
                                        Pilih logo
                                    </label>
                                    <input
                                        id="logo"
                                        name="logo"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        className="sr-only"
                                        onChange={(event) =>
                                            selectLogo(event.target.files?.[0])
                                        }
                                    />
                                    <InputError message={errors.logo} />
                                    <p className="text-xs text-muted-foreground">
                                        JPG, PNG, atau WebP. Maksimal 2 MB dan
                                        2.000 × 2.000 piksel.
                                    </p>

                                    {gym.logo_url && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={removeLogo}
                                            disabled={
                                                processing || removingLogo
                                            }
                                        >
                                            <Trash2 />
                                            {removingLogo
                                                ? 'Menghapus...'
                                                : 'Hapus logo'}
                                        </Button>
                                    )}
                                </div>

                                {progress && (
                                    <div
                                        className="h-1.5 overflow-hidden rounded-full bg-muted"
                                        aria-label={`Unggahan ${progress.percentage}%`}
                                    >
                                        <div
                                            className="h-full bg-primary transition-[width]"
                                            style={{
                                                width: `${progress.percentage}%`,
                                            }}
                                        />
                                    </div>
                                )}

                                <div className="grid gap-2 border-t pt-6">
                                    <Button
                                        type="submit"
                                        disabled={processing || removingLogo}
                                        data-test="save-gym-settings"
                                    >
                                        <Save />
                                        {processing
                                            ? 'Menyimpan...'
                                            : 'Simpan pengaturan'}
                                    </Button>
                                    {recentlySuccessful && (
                                        <p className="text-center text-xs text-muted-foreground">
                                            Perubahan tersimpan.
                                        </p>
                                    )}
                                </div>
                            </aside>
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}

EditGymSettings.layout = {
    breadcrumbs: [{ title: 'Pengaturan Gym', href: edit() }],
};
