import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export function MembershipPlanStatusBadge({
    isActive,
    label,
}: {
    isActive: boolean;
    label: string;
}) {
    return (
        <Badge
            variant="outline"
            className={cn(
                isActive
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300'
                    : 'border-zinc-200 bg-zinc-50 text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300',
            )}
        >
            <span
                className={cn(
                    'size-1.5 rounded-full',
                    isActive ? 'bg-emerald-500' : 'bg-zinc-400',
                )}
            />
            {label}
        </Badge>
    );
}
