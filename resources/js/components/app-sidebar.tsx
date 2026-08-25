import { Link, usePage } from '@inertiajs/react';
import {
    CircleDollarSign,
    LayoutDashboard,
    Tickets,
    UsersRound,
} from 'lucide-react';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as membersIndex } from '@/routes/members';
import { index as membershipPlansIndex } from '@/routes/membership-plans';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { auth } = usePage().props;
    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutDashboard,
        },
        ...(auth.permissions.operate_front_desk
            ? [
                  {
                      title: 'Member',
                      href: membersIndex(),
                      icon: UsersRound,
                  },
                  {
                      title: 'Paket Membership',
                      href: membershipPlansIndex(),
                      icon: Tickets,
                  },
                  {
                      title: 'Pembayaran',
                      href: PaymentController.index(),
                      icon: CircleDollarSign,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader className="border-b border-sidebar-border/70 p-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter className="border-t border-sidebar-border/70 p-3">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
