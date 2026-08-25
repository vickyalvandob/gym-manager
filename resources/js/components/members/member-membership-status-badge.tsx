import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { MemberMembershipStatus } from '@/types';

export function MemberMembershipStatusBadge({
    status,
    label,
    isExpiringSoon = false,
}: {
    status: MemberMembershipStatus;
    label: string;
    isExpiringSoon?: boolean;
}) {
    return (
        <Badge
            variant="outline"
            className={cn(
                status === 'active' &&
                    !isExpiringSoon &&
                    'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300',
                status === 'active' &&
                    isExpiringSoon &&
                    'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
                status === 'upcoming' &&
                    'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300',
                status === 'expired' &&
                    'border-zinc-200 bg-zinc-50 text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300',
            )}
        >
            <span
                className={cn(
                    'size-1.5 rounded-full',
                    status === 'active' &&
                        (isExpiringSoon ? 'bg-amber-500' : 'bg-emerald-500'),
                    status === 'upcoming' && 'bg-sky-500',
                    status === 'expired' && 'bg-zinc-400',
                )}
            />
            {label}
        </Badge>
    );
}
