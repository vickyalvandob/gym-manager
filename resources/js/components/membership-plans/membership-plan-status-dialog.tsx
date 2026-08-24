import { Form } from '@inertiajs/react';
import { Power, PowerOff } from 'lucide-react';
import UpdateMembershipPlanStatusController from '@/actions/App/Http/Controllers/UpdateMembershipPlanStatusController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

export function MembershipPlanStatusDialog({
    membershipPlanId,
    membershipPlanName,
    isActive,
    buttonVariant = 'outline',
}: {
    membershipPlanId: number;
    membershipPlanName: string;
    isActive: boolean;
    buttonVariant?: 'outline' | 'ghost';
}) {
    const activating = !isActive;

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant={buttonVariant}
                    size="sm"
                    title={activating ? 'Aktifkan paket' : 'Nonaktifkan paket'}
                >
                    {activating ? <Power /> : <PowerOff />}
                    <span
                        className={buttonVariant === 'ghost' ? 'sr-only' : ''}
                    >
                        {activating ? 'Aktifkan' : 'Nonaktifkan'}
                    </span>
                </Button>
            </DialogTrigger>
            <DialogContent className="rounded-lg shadow-md">
                <DialogHeader>
                    <DialogTitle>
                        {activating ? 'Aktifkan paket' : 'Nonaktifkan paket'}
                    </DialogTitle>
                    <DialogDescription>
                        Status {membershipPlanName} akan diubah menjadi{' '}
                        {activating ? 'aktif' : 'nonaktif'}.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...UpdateMembershipPlanStatusController.form(
                        membershipPlanId,
                    )}
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="outline">
                                    Batal
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                variant={activating ? 'default' : 'destructive'}
                                disabled={processing}
                            >
                                {activating ? <Power /> : <PowerOff />}
                                {processing
                                    ? 'Menyimpan...'
                                    : activating
                                      ? 'Aktifkan'
                                      : 'Nonaktifkan'}
                            </Button>
                            <input
                                type="hidden"
                                name="is_active"
                                value={activating ? '1' : '0'}
                            />
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
