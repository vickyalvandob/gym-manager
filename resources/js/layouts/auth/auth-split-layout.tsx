import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
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
        <div className="min-h-dvh bg-background lg:grid lg:grid-cols-[minmax(28rem,0.9fr)_minmax(34rem,1.1fr)]">
            <aside className="relative hidden h-dvh overflow-hidden bg-zinc-950 lg:sticky lg:top-0 lg:block">
                <img
                    src="/images/gymlo-auth.webp"
                    alt="Interior gym modern dengan area latihan yang tertata"
                    className="absolute inset-0 size-full object-cover"
                />
                <div className="absolute inset-0 bg-zinc-950/25" />
                <div className="absolute inset-0 bg-linear-to-t from-zinc-950 via-zinc-950/10 to-zinc-950/45" />

                <div className="relative z-10 flex h-full flex-col justify-between p-8 text-white xl:p-12">
                    <Link
                        href={home()}
                        className="flex w-fit items-center gap-3 rounded-md focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-4 focus-visible:ring-offset-zinc-950 focus-visible:outline-none"
                    >
                        <span className="flex size-10 items-center justify-center rounded-md bg-white text-zinc-950">
                            <AppLogoIcon
                                className="size-5"
                                strokeWidth={2.25}
                            />
                        </span>
                        <span className="text-base font-semibold tracking-[-0.02em]">
                            {name}
                        </span>
                    </Link>

                    <div className="max-w-xl">
                        <div className="mb-6 flex items-center gap-3 text-xs font-medium tracking-[0.15em] text-zinc-300 uppercase">
                            <span className="h-px w-8 bg-primary" />
                            Sistem manajemen gym
                        </div>
                        <p className="mt-5 max-w-lg text-sm leading-7 text-zinc-300 xl:text-base">
                            Kelola member, pembayaran, check-in, trainer, dan
                            Personal Training dari data yang sama.
                        </p>
                    </div>
                </div>
            </aside>

            <main className="flex min-h-dvh flex-col">
                <div className="flex h-[4.5rem] shrink-0 items-center justify-between border-b px-5 sm:px-8 lg:px-10 xl:px-14">
                    <Link
                        href={home()}
                        className="inline-flex items-center gap-2 rounded-md text-sm font-medium text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <ArrowLeft className="size-4" aria-hidden />
                        <span className="hidden sm:inline">
                            Kembali ke beranda
                        </span>
                        <span className="sm:hidden">Beranda</span>
                    </Link>

                    <Link
                        href={home()}
                        className="flex items-center gap-2.5 rounded-md font-semibold focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none lg:hidden"
                    >
                        <span className="flex size-8 items-center justify-center rounded-md bg-foreground text-background">
                            <AppLogoIcon
                                className="size-4"
                                strokeWidth={2.25}
                            />
                        </span>
                        {name}
                    </Link>
                </div>

                <div className="flex flex-1 items-center px-5 py-10 sm:px-8 sm:py-14 lg:px-10 xl:px-14">
                    <div className="mx-auto w-full max-w-xl">
                        <div className="max-w-lg">
                            <p className="text-xs font-semibold tracking-[0.18em] text-primary uppercase">
                                {name} Workspace
                            </p>
                            <h1 className="mt-4 text-3xl leading-tight font-semibold tracking-[-0.04em] text-balance sm:text-4xl">
                                {title}
                            </h1>
                            <p className="mt-3 max-w-md text-sm leading-6 text-pretty text-muted-foreground sm:text-base">
                                {description}
                            </p>
                        </div>

                        <div className="mt-8 border-t pt-8">{children}</div>
                    </div>
                </div>

                <div className="px-5 pb-6 text-center text-[11px] leading-5 text-muted-foreground sm:px-8 lg:text-left xl:px-14">
                    Data akun hanya digunakan untuk mengakses workspace Anda.
                </div>
            </main>
        </div>
    );
}
