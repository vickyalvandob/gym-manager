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
import { SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { index as gymsIndex, switchMethod as switchGym } from '@/routes/gyms';

export function GymSwitcher() {
    const { auth } = usePage().props;

    if (!auth.currentGym) {
        return null;
    }

    return (
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <SidebarMenuButton
                        size="lg"
                        tooltip={`Gym aktif: ${auth.currentGym.name}`}
                    >
                        <div className="text-left text-sm leading-tight">
                            <span className="block truncate font-medium">
                                {auth.currentGym.name}
                            </span>
                        </div>
                        <ChevronsUpDown className="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    className="w-(--radix-dropdown-menu-trigger-width) min-w-64 rounded-lg"
                    align="start"
                    side="right"
                    sideOffset={4}
                >
                    <DropdownMenuLabel className="text-xs text-muted-foreground">
                        Pilih gym
                    </DropdownMenuLabel>
                    {auth.availableGyms.map((gym) => (
                        <DropdownMenuItem key={gym.id} asChild>
                            <Link
                                href={switchGym(gym.id)}
                                method="put"
                                as="button"
                                className="w-full"
                            >
                                <span className="min-w-0 flex-1 text-left truncate">
                                    {gym.name}
                                </span>
                                {gym.id === auth.currentGym?.id && (
                                    <Check className="size-4" />
                                )}
                            </Link>
                        </DropdownMenuItem>
                    ))}
                    {auth.role === 'owner' && (
                        <>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem asChild>
                                <Link href={gymsIndex()}>
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
