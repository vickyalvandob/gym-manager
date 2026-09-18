import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';

export default function Unavailable() {
    return (
        <main className="flex min-h-screen items-center justify-center bg-background p-6 text-foreground">
            <Head title="Layanan sementara tidak tersedia" />
            <section className="w-full max-w-md space-y-5 rounded-xl border p-6">
                <p className="text-sm font-medium text-primary">My Gymlo</p>
                <h1 className="text-xl font-semibold">
                    Layanan sementara tidak tersedia
                </h1>
                <p className="text-sm leading-6 text-muted-foreground">
                    Akses gym sedang dibatasi. Hubungi pengelola gym untuk
                    informasi lebih lanjut.
                </p>
                <div className="flex gap-2">
                    <Button onClick={() => router.reload()}>Coba lagi</Button>
                    <Button variant="outline" asChild>
                        <Link href={logout()} as="button">
                            Keluar
                        </Link>
                    </Button>
                </div>
            </section>
        </main>
    );
}
