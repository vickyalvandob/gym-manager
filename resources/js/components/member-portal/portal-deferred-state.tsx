import { router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

export function PortalDataSkeleton({ rows = 5 }: { rows?: number }) {
    return (
        <div className="overflow-hidden rounded-xl border bg-background">
            <div className="space-y-2 border-b p-5 sm:p-6">
                <Skeleton className="h-5 w-44" />
                <Skeleton className="h-3 w-72 max-w-full" />
            </div>
            <div className="divide-y px-5 sm:px-6">
                {Array.from({ length: rows }, (_, index) => (
                    <div
                        key={index}
                        className="flex items-center justify-between gap-5 py-5"
                    >
                        <div className="flex-1 space-y-2">
                            <Skeleton className="h-4 w-48 max-w-full" />
                            <Skeleton className="h-3 w-32 max-w-full" />
                        </div>
                        <Skeleton className="h-7 w-20" />
                    </div>
                ))}
            </div>
        </div>
    );
}

export function PortalDataError({
    reloading,
    only,
}: {
    reloading: boolean;
    only: string[];
}) {
    return (
        <div className="rounded-xl border border-dashed bg-background px-6 py-14 text-center">
            <RefreshCw className="mx-auto size-8 text-muted-foreground" />
            <p className="mt-4 text-sm font-semibold">
                Data belum dapat dimuat
            </p>
            <p className="mt-1 text-xs text-muted-foreground">
                Coba muat ulang tanpa meninggalkan halaman ini.
            </p>
            <Button
                type="button"
                variant="outline"
                size="sm"
                className="mt-5"
                disabled={reloading}
                onClick={() => router.reload({ only })}
            >
                <RefreshCw className={cn(reloading && 'animate-spin')} />
                {reloading ? 'Memuat...' : 'Coba Lagi'}
            </Button>
        </div>
    );
}
