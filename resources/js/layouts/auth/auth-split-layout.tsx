import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="grid min-h-dvh bg-background lg:grid-cols-[minmax(0,1.05fr)_minmax(26rem,0.95fr)]">
            <div className="relative hidden min-h-dvh overflow-hidden lg:block">
                <img
                    src="/images/gymflow-auth.webp"
                    alt="Interior gym modern dengan area latihan yang tertata"
                    className="absolute inset-0 size-full object-cover"
                />
                <Link
                    href={home()}
                    className="absolute top-8 left-8 z-10 flex items-center gap-3 text-lg font-semibold text-zinc-950"
                >
                    <span className="flex size-10 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <AppLogoIcon className="size-5" strokeWidth={2.25} />
                    </span>
                    {name}
                </Link>
            </div>
            <div className="flex min-h-dvh w-full items-center px-5 py-10 sm:px-8 lg:p-12">
                <div className="mx-auto flex w-full max-w-sm flex-col justify-center gap-7">
                    <Link
                        href={home()}
                        className="flex items-center gap-2.5 font-semibold lg:hidden"
                    >
                        <span className="flex size-9 items-center justify-center rounded-md bg-primary text-primary-foreground">
                            <AppLogoIcon
                                className="size-4.5"
                                strokeWidth={2.25}
                            />
                        </span>
                        {name}
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left">
                        <h1 className="text-2xl font-semibold">{title}</h1>
                        <p className="text-sm text-balance text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
