import { Link, usePage } from '@inertiajs/react';
import {
    ChartNoAxesCombined,
    CalendarClock,
    CircleDollarSign,
    Dumbbell,
    LayoutDashboard,
    LogIn,
    Tickets,
    UserRoundCog,
    UsersRound,
} from 'lucide-react';
import CheckInController from '@/actions/App/Http/Controllers/CheckInController';
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
import { edit as profileEdit } from '@/routes/profile';
import { index as ptPackagesIndex } from '@/routes/pt-packages';
import { index as ptSessionsIndex } from '@/routes/pt-sessions';
import { index as reportsIndex } from '@/routes/reports';
import { index as trainerMembersIndex } from '@/routes/trainer-members';
import { index as trainersIndex } from '@/routes/trainers';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { auth } = usePage().props;
    const trainerNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutDashboard,
        },
        {
            title: 'Jadwal Saya',
            href: ptSessionsIndex({ query: { scope: 'upcoming' } }),
            icon: CalendarClock,
        },
        {
            title: 'Member Saya',
            href: trainerMembersIndex(),
            icon: UsersRound,
        },
        {
            title: 'Riwayat Sesi',
            href: ptSessionsIndex({ query: { scope: 'history' } }),
            icon: Dumbbell,
        },
        {
            title: 'Profil',
            href: profileEdit(),
            icon: UserRoundCog,
        },
    ];
    const managementNavItems: NavItem[] = [
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
                  {
                      title: 'Check-in',
                      href: CheckInController.index(),
                      icon: LogIn,
                  },
                  {
                      title: 'Trainer',
                      href: trainersIndex(),
                      icon: Dumbbell,
                  },
                  {
                      title: 'Paket PT',
                      href: ptPackagesIndex(),
                      icon: Tickets,
                  },
                  {
                      title: 'Sesi PT',
                      href: ptSessionsIndex(),
                      icon: CalendarClock,
                  },
              ]
            : []),
        ...(auth.permissions.view_reports
            ? [
                  {
                      title: 'Laporan',
                      href: reportsIndex(),
                      icon: ChartNoAxesCombined,
                  },
              ]
            : []),
    ];
    const mainNavItems =
        auth.role === 'trainer' ? trainerNavItems : managementNavItems;

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
