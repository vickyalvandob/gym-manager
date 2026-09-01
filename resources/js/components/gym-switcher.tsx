import { Link, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown, Settings2 } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';
import { index as gymsIndex, switchMethod as switchGym } from '@/routes/gyms';
import type { GymRole } from '@/types';

const gymRoleLabels: Record<GymRole, string> = {
    owner: 'Owner',
    admin: 'Front Office',
    trainer: 'Trainer',
};

export function GymSwitcher() {
    const { auth } = usePage().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    if (!auth.currentGym) {
        return null;
    }

    return (
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <SidebarMenuButton
                        size="lg"
                        className="h-auto min-h-14 rounded-lg border border-sidebar-border/80 bg-sidebar-accent/25 px-2.5 py-2 text-sidebar-foreground group-data-[collapsible=icon]:border-transparent group-data-[collapsible=icon]:bg-transparent group-data-[collapsible=icon]:p-1! hover:bg-sidebar-accent/60 data-[state=open]:bg-sidebar-accent"
                        tooltip={`Gym aktif: ${auth.currentGym.name}`}
                    >
                        <div className="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-md border border-sidebar-border bg-sidebar">
                            {auth.currentGym.logo_url ? (
                                <img
                                    src={auth.currentGym.logo_url}
                                    alt=""
                                    className="size-full object-contain"
                                />
                            ) : (
                                <Building2
                                    className="size-4 text-sidebar-foreground/70"
                                    aria-hidden
                                />
                            )}
                        </div>
                        <div className="grid min-w-0 flex-1 text-left leading-tight">
                            <span className="text-[10px] font-semibold tracking-[0.12em] text-sidebar-foreground/55 uppercase">
                                Gym aktif
                            </span>
                            <span className="mt-0.5 block truncate text-sm font-medium">
                                {auth.currentGym.name}
                            </span>
                        </div>
                        <ChevronsUpDown className="ml-auto size-3.5 text-sidebar-foreground/55" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    className="w-(--radix-dropdown-menu-trigger-width) min-w-72 rounded-lg border p-1.5 shadow-none"
                    align="start"
                    side={
                        isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                              ? 'right'
                              : 'bottom'
                    }
                    sideOffset={8}
                >
                    <DropdownMenuLabel className="px-2 py-2">
                        <span className="block text-xs font-semibold">
                            Pilih workspace
                        </span>
                        <span className="mt-0.5 block text-[11px] font-normal text-muted-foreground">
                            {auth.availableGyms.length} gym tersedia
                        </span>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    {auth.availableGyms.map((gym) => (
                        <DropdownMenuItem
                            key={gym.id}
                            className={cn(
                                'rounded-md p-0',
                                gym.id === auth.currentGym?.id && 'bg-accent',
                            )}
                            asChild
                        >
                            <Link
                                href={switchGym(gym.id)}
                                method="put"
                                as="button"
                                className="flex w-full items-center gap-3 px-2 py-2.5"
                            >
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-md border bg-background text-xs font-semibold">
                                    {gym.name.charAt(0).toUpperCase()}
                                </span>
                                <span className="grid min-w-0 flex-1 text-left leading-tight">
                                    <span className="truncate text-sm font-medium">
                                        {gym.name}
                                    </span>
                                    <span className="mt-0.5 flex items-center gap-1.5 text-[11px] text-muted-foreground">
                                        <span
                                            className={cn(
                                                'size-1.5 rounded-full',
                                                gym.status === 'active'
                                                    ? 'bg-emerald-500'
                                                    : 'bg-destructive',
                                            )}
                                        />
                                        {gymRoleLabels[gym.role]}
                                    </span>
                                </span>
                                {gym.id === auth.currentGym?.id && (
                                    <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <Check className="size-3.5" />
                                    </span>
                                )}
                            </Link>
                        </DropdownMenuItem>
                    ))}
                    {auth.role === 'owner' && (
                        <>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                className="rounded-md px-2 py-2.5"
                                asChild
                            >
                                <Link href={gymsIndex()} prefetch>
                                    <Settings2 className="size-4" />
                                    Kelola gym
                                </Link>
                            </DropdownMenuItem>
                        </>
                    )}
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    );
}
