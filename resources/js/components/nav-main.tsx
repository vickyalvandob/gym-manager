import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavSection } from '@/types';

export function NavMain({ sections = [] }: { sections: NavSection[] }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { setOpenMobile } = useSidebar();

    return (
        <nav aria-label="Menu aplikasi">
            {sections.map((section) => (
                <SidebarGroup
                    key={section.title}
                    className="px-2 py-1.5 first:pt-3 last:pb-3"
                >
                    <SidebarGroupLabel className="h-7 px-2 text-[10px] font-semibold tracking-[0.12em] text-sidebar-foreground/45 uppercase">
                        {section.title}
                    </SidebarGroupLabel>
                    <SidebarMenu className="gap-0.5">
                        {section.items.map((item) => (
                            <SidebarMenuItem key={item.title}>
                                {item.disabled ? (
                                    <SidebarMenuButton
                                        disabled
                                        aria-disabled="true"
                                        className="h-9 rounded-md px-2.5"
                                        tooltip={{
                                            children:
                                                item.disabledReason ??
                                                item.title,
                                        }}
                                    >
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </SidebarMenuButton>
                                ) : (
                                    <SidebarMenuButton
                                        asChild
                                        className="h-9 rounded-md px-2.5 text-sidebar-foreground/75 hover:text-sidebar-foreground data-[active=true]:bg-primary/10 data-[active=true]:font-semibold data-[active=true]:text-primary data-[active=true]:[&>svg]:text-primary"
                                        isActive={isCurrentOrParentUrl(
                                            item.href,
                                        )}
                                        tooltip={{ children: item.title }}
                                    >
                                        <Link
                                            href={item.href}
                                            prefetch
                                            onClick={() => setOpenMobile(false)}
                                        >
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                )}
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </nav>
    );
}
