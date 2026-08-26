import { Badge } from '@/components/ui/badge';
import type { PtSessionStatus } from '@/types';

const variants: Record<
    PtSessionStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    scheduled: 'default',
    completed: 'secondary',
    cancelled: 'outline',
    no_show: 'destructive',
};

export function PtSessionStatusBadge({
    status,
    label,
}: {
    status: PtSessionStatus;
    label: string;
}) {
    return <Badge variant={variants[status]}>{label}</Badge>;
}
