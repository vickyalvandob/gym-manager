import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { MemberPortalPaginator } from '@/types';

export function PortalPagination<T>({
    paginator,
}: {
    paginator: MemberPortalPaginator<T>;
}) {
    if (paginator.last_page <= 1) {
        return null;
    }

    const previous = paginator.links[0];
    const next = paginator.links[paginator.links.length - 1];

    return (
        <div className="flex flex-col gap-3 border-t px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p className="text-xs text-muted-foreground">
                Menampilkan {paginator.from ?? 0}–{paginator.to ?? 0} dari{' '}
                {paginator.total} data
            </p>
            <div className="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!previous?.url}
                    asChild={Boolean(previous?.url)}
                >
                    {previous?.url ? (
                        <Link href={previous.url} preserveScroll>
                            <ChevronLeft /> Sebelumnya
                        </Link>
                    ) : (
                        <span>
                            <ChevronLeft /> Sebelumnya
                        </span>
                    )}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!next?.url}
                    asChild={Boolean(next?.url)}
                >
                    {next?.url ? (
                        <Link href={next.url} preserveScroll>
                            Berikutnya <ChevronRight />
                        </Link>
                    ) : (
                        <span>
                            Berikutnya <ChevronRight />
                        </span>
                    )}
                </Button>
            </div>
        </div>
    );
}
