import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { name, auth } = usePage().props;

    return (
        <>
            {auth.currentGym?.logo_url ? (
                <div className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md border bg-background">
                    <img
                        src={auth.currentGym.logo_url}
                        alt={`Logo ${auth.currentGym.name}`}
                        className="size-full object-contain"
                    />
                </div>
            ) : (
                <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                    <AppLogoIcon className="size-4.5" strokeWidth={2.25} />
                </div>
            )}
            <div className="ml-1 grid min-w-0 flex-1 text-left text-sm leading-tight">
                <span className="truncate font-semibold">{name}</span>
                <span className="truncate text-xs text-sidebar-foreground/60">
                    {auth.currentGym?.name ??
                        (auth.isPlatformAdmin
                            ? 'Platform administration'
                            : 'Gym management')}
                </span>
            </div>
        </>
    );
}
