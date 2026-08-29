import { usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage().props;

    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-4 border-b border-sidebar-border/70 bg-background px-4 md:px-6">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            {auth.currentGym && (
                <div className="flex min-w-0 items-center gap-3 text-sm">
                    <div className="min-w-0 text-right">
                        <p className="truncate font-medium">
                            {auth.currentGym.name}
                        </p>
                        <p className="hidden text-xs text-muted-foreground sm:block">
                            {auth.roleLabel}
                        </p>
                    </div>
                    <span
                        className={
                            auth.currentGym.status === 'active'
                                ? 'size-2 shrink-0 rounded-full bg-emerald-500'
                                : 'size-2 shrink-0 rounded-full bg-red-500'
                        }
                        aria-label={
                            auth.currentGym.status === 'active'
                                ? 'Gym aktif'
                                : 'Gym ditangguhkan'
                        }
                    />
                </div>
            )}
        </header>
    );
}
