import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogo from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import { dashboard, history, profile, schedule } from '@/routes/trainer';
import { index as sessions } from '@/routes/trainer/pt-sessions';
import { index as clients } from '@/routes/trainer-members';

export default function TrainerLayout({ children }: { children: ReactNode }) {
    const { auth, url } = { ...usePage().props, url: usePage().url };
    const items = [
        { label: 'Dashboard', href: dashboard() },
        { label: 'Client Saya', href: clients() },
        { label: 'Jadwal', href: schedule() },
        { label: 'Sesi PT', href: sessions() },
        { label: 'Riwayat', href: history() },
        { label: 'Profil', href: profile() },
    ];

    return (
        <div className="min-h-screen bg-background">
            <header className="border-b">
                <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                    <Link
                        href={dashboard()}
                        className="flex items-center gap-2"
                        aria-label="Gymlo Trainer"
                    >
                        <AppLogo />
                    </Link>
                    <div className="flex min-w-0 items-center gap-3">
                        <p className="hidden truncate text-sm text-muted-foreground sm:block">
                            {auth.currentGym?.name} · {auth.user?.name}
                        </p>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={logout()} as="button">
                                Keluar
                            </Link>
                        </Button>
                    </div>
                </div>
                <nav
                    aria-label="Menu Trainer"
                    className="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 pb-3 sm:px-6"
                >
                    {items.map((item) => {
                        const active = url.split('?')[0] === item.href.url;

                        return (
                            <Link
                                key={item.label}
                                href={item.href}
                                prefetch
                                aria-current={active ? 'page' : undefined}
                                className={cn(
                                    'shrink-0 rounded-md px-3 py-2 text-sm font-medium focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                                    active
                                        ? 'bg-primary/10 text-primary'
                                        : 'text-muted-foreground hover:bg-muted',
                                )}
                            >
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>
            </header>
            <main className="mx-auto max-w-7xl">{children}</main>
        </div>
    );
}
