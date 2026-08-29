import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export function PlatformStatusBadge({
    status,
    label,
}: {
    status: string;
    label: string;
}) {
    return (
        <Badge
            variant="outline"
            className={cn(
                'font-normal',
                ['active', 'trialing'].includes(status) &&
                    'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300',
                ['past_due', 'expired'].includes(status) &&
                    'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300',
                ['suspended', 'cancelled'].includes(status) &&
                    'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300',
            )}
        >
            {label}
        </Badge>
    );
}
