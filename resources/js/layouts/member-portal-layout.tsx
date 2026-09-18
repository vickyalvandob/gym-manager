import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CreditCard,
    Dumbbell,
    History,
    Home,
    LogOut,
    Settings,
    QrCode,
    Tickets,
    ShieldCheck,
} from 'lucide-react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { MemberAvatar } from '@/components/members/member-avatar';
import { MemberStatusBadge } from '@/components/members/member-status-badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import MemberPortalController from '@/routes/my';
import type { MemberPortalBaseProps } from '@/types';

type PortalNavigationItem = {
    label: string;
    description: string;
    href: string;
    icon: typeof Home;
};

export default function MemberPortalLayout({
    children,
}: {
    children: ReactNode;
}) {
    const page = usePage<MemberPortalBaseProps>();
    const { auth, member, name, profiles } = page.props;
    const currentPath = page.url.split('?')[0];
    const navigation: PortalNavigationItem[] = [
        {
            label: 'Beranda',
            description: 'QR dan membership',
            href: MemberPortalController.show().url,
            icon: Home,
        },
        {
            label: 'QR Check-in',
            description: 'Kartu member digital',
            href: MemberPortalController.qr().url,
            icon: QrCode,
        },
        {
            label: 'Membership',
            description: 'Paket dan masa berlaku',
            href: MemberPortalController.membership().url,
            icon: Tickets,
        },
        {
            label: 'Riwayat Check-in',
            description: 'Catatan kunjungan',
            href: MemberPortalController.checkIns().url,
            icon: History,
        },
        {
            label: 'Pembayaran',
            description: 'Invoice dan status',
            href: MemberPortalController.payments().url,
            icon: CreditCard,
        },
        {
            label: 'Personal Training',
            description: 'Paket dan jadwal',
            href: MemberPortalController.personalTraining().url,
            icon: Dumbbell,
        },
        {
            label: 'Profil',
            description: 'Informasi kontak',
            href: MemberPortalController.settings().url,
            icon: Settings,
        },
        {
            label: 'Keamanan',
            description: 'Kata sandi akun',
            href: MemberPortalController.security().url,
            icon: ShieldCheck,
        },
    ];

    return (
        <div className="min-h-screen bg-muted/20 text-foreground">
            <Head title={`Portal ${member.name}`} />

            <header className="sticky top-0 z-40 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/85">
                <div className="mx-auto flex h-16 max-w-[90rem] items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                    <Link
                        href={MemberPortalController.show()}
                        className="flex min-w-0 items-center gap-3"
                        prefetch
                    >
                        <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                            <AppLogoIcon
                                className="size-5"
                                strokeWidth={2.25}
                            />
                        </span>
                        <span className="min-w-0">
                            <span className="block truncate text-sm font-semibold">
                                {name}
                            </span>
                            <span className="block truncate text-xs text-muted-foreground">
                                My Gymlo · {member.gym.name}
                            </span>
                        </span>
                    </Link>

                    <div className="flex items-center gap-2">
                        {profiles.length > 1 && (
                            <Select
                                value={String(member.id)}
                                onValueChange={(value) =>
                                    router.visit(
                                        MemberPortalController.show({
                                            query: { profile: value },
                                        }),
                                    )
                                }
                            >
                                <SelectTrigger
                                    className="w-36 sm:w-52"
                                    aria-label="Pilih profil member"
                                >
                                    <SelectValue placeholder="Pilih profil" />
                                </SelectTrigger>
                                <SelectContent align="end">
                                    {profiles.map((profile) => (
                                        <SelectItem
                                            key={profile.id}
                                            value={String(profile.id)}
                                        >
                                            {profile.gym_name} ·{' '}
                                            {profile.member_number}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
                        <span className="hidden max-w-48 truncate text-sm text-muted-foreground md:block">
                            {auth.user?.email}
                        </span>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={logout()} method="post" as="button">
                                <LogOut />
                                <span className="hidden sm:inline">Keluar</span>
                            </Link>
                        </Button>
                    </div>
                </div>
            </header>

            <div className="border-b bg-background lg:hidden">
                <nav
                    className="mx-auto flex max-w-[90rem] gap-1 overflow-x-auto px-4 py-2 sm:px-6"
                    aria-label="Menu portal member"
                >
                    {navigation.map((item) => {
                        const isActive = currentPath === item.href;

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                prefetch
                                aria-current={isActive ? 'page' : undefined}
                                className={cn(
                                    'flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                    isActive
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                )}
                            >
                                <item.icon className="size-4" />
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>
            </div>

            <div className="mx-auto grid max-w-[90rem] lg:grid-cols-[17rem_minmax(0,1fr)]">
                <aside className="hidden border-r bg-background lg:block">
                    <div className="sticky top-16 flex max-h-[calc(100vh-4rem)] flex-col gap-6 overflow-y-auto p-5">
                        <div className="rounded-xl border bg-muted/20 p-4">
                            <div className="flex items-center gap-3">
                                <MemberAvatar
                                    name={member.name}
                                    photoUrl={member.photo_url}
                                    className="size-11 rounded-lg"
                                />
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-semibold">
                                        {member.name}
                                    </p>
                                    <p className="mt-0.5 truncate font-mono text-xs text-muted-foreground">
                                        {member.member_number}
                                    </p>
                                </div>
                            </div>
                            <div className="mt-3 border-t pt-3">
                                <MemberStatusBadge
                                    status={member.status}
                                    label={member.status_label}
                                />
                            </div>
                        </div>

                        <nav
                            className="space-y-1"
                            aria-label="Menu portal member"
                        >
                            {navigation.map((item) => {
                                const isActive = currentPath === item.href;

                                return (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        prefetch
                                        aria-current={
                                            isActive ? 'page' : undefined
                                        }
                                        className={cn(
                                            'group flex items-center gap-3 rounded-lg px-3 py-2.5 transition-colors',
                                            isActive
                                                ? 'bg-primary/10 text-primary'
                                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                        )}
                                    >
                                        <item.icon className="size-4 shrink-0" />
                                        <span className="min-w-0">
                                            <span className="block truncate text-sm font-medium">
                                                {item.label}
                                            </span>
                                            <span
                                                className={cn(
                                                    'block truncate text-[11px]',
                                                    isActive
                                                        ? 'text-primary/70'
                                                        : 'text-muted-foreground',
                                                )}
                                            >
                                                {item.description}
                                            </span>
                                        </span>
                                    </Link>
                                );
                            })}
                        </nav>

                        <p className="mt-auto px-3 text-xs leading-5 text-muted-foreground">
                            Butuh bantuan? Hubungi Front Office{' '}
                            {member.gym.name}.
                        </p>
                    </div>
                </aside>

                <main className="min-w-0 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
