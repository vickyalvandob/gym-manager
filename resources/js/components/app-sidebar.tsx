import { Link, usePage } from '@inertiajs/react';
import {
    ChartNoAxesCombined,
    CalendarClock,
    CircleDollarSign,
    Dumbbell,
    LayoutDashboard,
    Landmark,
    LogIn,
    Settings2,
    Tickets,
    UserRoundCog,
    UsersRound,
    ShieldCheck,
    WalletCards,
} from 'lucide-react';
import CheckInController from '@/actions/App/Http/Controllers/CheckInController';
import GymSettingsController from '@/actions/App/Http/Controllers/GymSettingsController';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import AppLogo from '@/components/app-logo';
import { GymSwitcher } from '@/components/gym-switcher';
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
import { dashboard as platformDashboard } from '@/routes/platform';
import { index as platformBillingIndex } from '@/routes/platform/billing';
import { index as saasPlansIndex } from '@/routes/platform/saas-plans';
import { index as platformUsersIndex } from '@/routes/platform/users';
import { edit as profileEdit } from '@/routes/profile';
import { index as ptPackagesIndex } from '@/routes/pt-packages';
import { index as ptSessionsIndex } from '@/routes/pt-sessions';
import { index as reportsIndex } from '@/routes/reports';
import { index as staffIndex } from '@/routes/staff';
import { show as subscriptionShow } from '@/routes/subscription';
import { index as trainerMembersIndex } from '@/routes/trainer-members';
import { index as trainersIndex } from '@/routes/trainers';
import type { NavSection } from '@/types';

export function AppSidebar() {
    const { auth } = usePage().props;
    const isPlatformWorkspace =
        auth.isPlatformAdmin && auth.currentGym === null;
    const hasSubscriptionAccess =
        auth.subscription === null || auth.subscription.grants_access;
    const subscriptionBlockedReason = auth.subscription?.has_pending_payment
        ? 'Pembayaran subscription sedang menunggu persetujuan.'
        : 'Subscription berakhir. Selesaikan pembayaran untuk membuka menu ini.';
    const platformNavSections: NavSection[] = [
        {
            title: 'Platform',
            items: [
                {
                    title: 'Ringkasan',
                    href: platformDashboard(),
                    icon: ShieldCheck,
                },
                {
                    title: 'Pengguna',
                    href: platformUsersIndex(),
                    icon: UsersRound,
                },
                {
                    title: 'Paket SaaS',
                    href: saasPlansIndex(),
                    icon: WalletCards,
                },
                {
                    title: 'Billing Subscription',
                    href: platformBillingIndex(),
                    icon: Landmark,
                },
            ],
        },
    ];
    const trainerNavSections: NavSection[] = [
        {
            title: 'Hari ini',
            items: [
                {
                    title: 'Ringkasan',
                    href: dashboard(),
                    icon: LayoutDashboard,
                },
                {
                    title: 'Jadwal Saya',
                    href: ptSessionsIndex({ query: { scope: 'upcoming' } }),
                    icon: CalendarClock,
                },
            ],
        },
        {
            title: 'Member & sesi',
            items: [
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
            ],
        },
        {
            title: 'Akun',
            items: [
                {
                    title: 'Profil Saya',
                    href: profileEdit(),
                    icon: UserRoundCog,
                },
            ],
        },
    ];
    const managementNavSections: NavSection[] = [
        {
            title: 'Utama',
            items: [
                {
                    title: 'Ringkasan',
                    href: dashboard(),
                    icon: LayoutDashboard,
                },
            ],
        },
        ...(auth.permissions.operate_front_desk
            ? [
                  {
                      title: 'Membership',
                      items: [
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
                              title: 'Check-in',
                              href: CheckInController.index(),
                              icon: LogIn,
                          },
                      ],
                  },
                  {
                      title: 'Personal Trainer',
                      items: [
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
                              title: 'Jadwal PT',
                              href: ptSessionsIndex(),
                              icon: CalendarClock,
                          },
                      ],
                  },
              ]
            : []),

        ...(auth.permissions.operate_front_desk
            ? [
                  {
                      title: 'Keuangan',
                      items: [
                          {
                              title: 'Pembayaran',
                              href: PaymentController.index({
                                  query: { type: 'membership' },
                              }),
                              icon: CircleDollarSign,
                          },
                      ],
                  },
              ]
            : []),
        ...(auth.permissions.view_reports
            ? [
                  {
                      title: 'Analitik',
                      items: [
                          {
                              title: 'Laporan',
                              href: reportsIndex(),
                              icon: ChartNoAxesCombined,
                          },
                      ],
                  },
              ]
            : []),
        ...(auth.permissions.manage_gym
            ? [
                  {
                      title: 'Pengaturan',
                      items: [
                          {
                              title: 'Staf Gym',
                              href: staffIndex(),
                              icon: UserRoundCog,
                          },
                          {
                              title: 'Profil Gym',
                              href: GymSettingsController.edit(),
                              icon: Settings2,
                          },
                          {
                              title: hasSubscriptionAccess
                                  ? 'Subscription'
                                  : auth.subscription?.has_pending_payment
                                    ? 'Menunggu Approval'
                                    : 'Bayar Subscription',
                              href: subscriptionShow(),
                              icon: WalletCards,
                          },
                      ],
                  },
              ]
            : []),
    ];
    const disableOperationalSections = (sections: NavSection[]): NavSection[] =>
        sections.map((section) => ({
            ...section,
            items: section.items.map((item) =>
                [
                    'Subscription',
                    'Menunggu Approval',
                    'Bayar Subscription',
                ].includes(item.title)
                    ? item
                    : {
                          ...item,
                          disabled: true,
                          disabledReason: subscriptionBlockedReason,
                      },
            ),
        }));
    const gymNavSections =
        auth.role === 'trainer' ? trainerNavSections : managementNavSections;
    const mainNavSections = isPlatformWorkspace
        ? platformNavSections
        : hasSubscriptionAccess
          ? gymNavSections
          : disableOperationalSections(gymNavSections);
    const homeHref = isPlatformWorkspace
        ? platformDashboard()
        : !hasSubscriptionAccess
          ? auth.role === 'owner'
              ? subscriptionShow()
              : profileEdit()
          : dashboard();

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader className="border-b border-sidebar-border/70 p-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={homeHref} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    {!isPlatformWorkspace && auth.currentGym && <GymSwitcher />}
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain sections={mainNavSections} />
            </SidebarContent>

            <SidebarFooter className="border-t border-sidebar-border/70 p-3">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
