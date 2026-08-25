import { Form } from '@inertiajs/react';
import { FilePlus2 } from 'lucide-react';
import CreateMembershipPaymentController from '@/actions/App/Http/Controllers/CreateMembershipPaymentController';
import { Button } from '@/components/ui/button';

export function CreateMembershipPaymentButton({
    membershipId,
    compact = false,
}: {
    membershipId: number;
    compact?: boolean;
}) {
    return (
        <Form
            {...CreateMembershipPaymentController.form(membershipId)}
            options={{ preserveScroll: true }}
        >
            {({ processing }) => (
                <Button
                    type="submit"
                    variant="outline"
                    size="sm"
                    disabled={processing}
                    title="Buat invoice"
                >
                    <FilePlus2 />
                    <span className={compact ? 'sr-only' : ''}>
                        {processing ? 'Membuat...' : 'Buat invoice'}
                    </span>
                </Button>
            )}
        </Form>
    );
}
