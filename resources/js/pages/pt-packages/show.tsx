import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Pencil, ReceiptText } from 'lucide-react';
import PtPackageController from '@/actions/App/Http/Controllers/PtPackageController';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate } from '@/lib/formatters';
import type { PtPackage } from '@/types';

export default function ShowPtPackage({
    ptPackage,
    canEdit,
}: {
    ptPackage: PtPackage;
    canEdit: boolean;
}) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title={ptPackage.name} />
            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-7 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <header className="flex items-start justify-between gap-4">
                    <div className="flex gap-3">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={PtPackageController.index()}>
                                <ArrowLeft />
                                <span className="sr-only">Kembali</span>
                            </Link>
                        </Button>
                        <div>
                            <p className="text-sm font-medium text-primary">
                                Personal Training
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold">
                                {ptPackage.name}
                            </h1>
                        </div>
                    </div>
                    {canEdit && (
                        <Button variant="outline" asChild>
                            <Link href={PtPackageController.edit(ptPackage.id)}>
                                <Pencil />
                                Edit paket
                            </Link>
                        </Button>
                    )}
                </header>
                <div className="grid gap-6 md:grid-cols-[1fr_19rem]">
                    <section className="border-y py-6">
                        <h2 className="text-base font-semibold">
                            Detail paket
                        </h2>
                        <p className="mt-3 text-sm leading-7 whitespace-pre-wrap text-muted-foreground">
                            {ptPackage.description ?? 'Tanpa deskripsi.'}
                        </p>
                        <dl className="mt-6 grid gap-px overflow-hidden rounded-lg border bg-border sm:grid-cols-2">
                            <Item
                                label="Jumlah sesi"
                                value={`${ptPackage.session_count} sesi`}
                                icon={<CalendarDays />}
                            />
                            <Item
                                label="Masa berlaku"
                                value={
                                    ptPackage.validity_days
                                        ? `${ptPackage.validity_days} hari`
                                        : 'Tanpa batas'
                                }
                                icon={<CalendarDays />}
                            />
                            <Item
                                label="Harga"
                                value={formatCurrency(
                                    ptPackage.price,
                                    auth.currentGym?.currency ?? 'IDR',
                                )}
                                icon={<ReceiptText />}
                            />
                            <Item
                                label="Penjualan"
                                value={`${ptPackage.sales_count} paket`}
                                icon={<ReceiptText />}
                            />
                        </dl>
                    </section>
                    <aside className="space-y-5 border-y py-6">
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Status
                            </p>
                            <p className="mt-1 font-medium">
                                {ptPackage.status_label}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Dibuat
                            </p>
                            <p className="mt-1 text-sm">
                                {formatDate(ptPackage.created_at)}
                            </p>
                        </div>
                        <p className="text-xs leading-5 text-muted-foreground">
                            Paket dinonaktifkan tetap tersimpan pada riwayat
                            transaksi member.
                        </p>
                    </aside>
                </div>
            </div>
        </>
    );
}

function Item({
    label,
    value,
    icon,
}: {
    label: string;
    value: string;
    icon: React.ReactNode;
}) {
    return (
        <div className="bg-background p-4">
            <div className="flex items-center gap-2 text-xs text-muted-foreground [&_svg]:size-4">
                {icon}
                {label}
            </div>
            <p className="mt-2 font-medium">{value}</p>
        </div>
    );
}

ShowPtPackage.layout = {
    breadcrumbs: [
        { title: 'Paket PT', href: PtPackageController.index() },
        { title: 'Detail paket', href: '#' },
    ],
};
