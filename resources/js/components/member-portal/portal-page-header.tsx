import type { ReactNode } from 'react';

export function PortalPageHeader({
    eyebrow,
    title,
    description,
    action,
}: {
    eyebrow: string;
    title: string;
    description: string;
    action?: ReactNode;
}) {
    return (
        <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div className="max-w-2xl">
                <p className="text-xs font-semibold tracking-[0.14em] text-primary uppercase">
                    {eyebrow}
                </p>
                <h1 className="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
                    {title}
                </h1>
                <p className="mt-2 text-sm leading-6 text-muted-foreground">
                    {description}
                </p>
            </div>
            {action}
        </header>
    );
}
