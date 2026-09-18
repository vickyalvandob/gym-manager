import { Head } from '@inertiajs/react';
import { PortalPageHeader } from '@/components/member-portal/portal-page-header';
import type { MemberPortalBaseProps } from '@/types';

export default function MemberQr({
    member,
    qr_code_data_url: qr,
}: MemberPortalBaseProps & { qr_code_data_url: string | null }) {
    return (
        <div className="mx-auto max-w-xl space-y-6">
            <Head title="QR Check-in" />
            <PortalPageHeader
                eyebrow="My Gymlo"
                title="QR Check-in"
                description="Tunjukkan QR ini kepada Front Office saat datang ke gym."
            />
            <section className="rounded-xl border bg-background p-6 text-center">
                {qr ? (
                    <img
                        src={qr}
                        alt={`QR check-in ${member.name}`}
                        className="mx-auto aspect-square w-full max-w-72 rounded-lg bg-white p-4"
                    />
                ) : (
                    <p>QR belum tersedia. Hubungi Front Office.</p>
                )}
                <h2 className="mt-5 font-semibold">{member.name}</h2>
                <p className="mt-1 font-mono text-sm text-muted-foreground">
                    {member.member_number}
                </p>
                <p className="mt-5 border-t pt-5 text-sm text-muted-foreground">
                    Sistem memeriksa status member, pembayaran membership, masa
                    berlaku, dan outlet saat check-in.
                </p>
            </section>
        </div>
    );
}
