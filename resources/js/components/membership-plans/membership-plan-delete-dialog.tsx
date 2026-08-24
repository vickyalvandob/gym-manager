import { Form } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import MembershipPlanController from '@/actions/App/Http/Controllers/MembershipPlanController';
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

export function MembershipPlanDeleteDialog({
    membershipPlanId,
    membershipPlanName,
    buttonVariant = 'outline',
}: {
    membershipPlanId: number;
    membershipPlanName: string;
    buttonVariant?: 'outline' | 'ghost';
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant={buttonVariant}
                    size="sm"
                    title="Hapus paket"
                >
                    <Trash2 />
                    <span
                        className={buttonVariant === 'ghost' ? 'sr-only' : ''}
                    >
                        Hapus
                    </span>
                </Button>
            </DialogTrigger>
            <DialogContent className="rounded-lg shadow-md">
                <DialogHeader>
                    <DialogTitle>Hapus paket membership?</DialogTitle>
                    <DialogDescription>
                        {membershipPlanName} akan dihapus permanen. Tindakan ini
                        tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...MembershipPlanController.destroy.form(membershipPlanId)}
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
                                variant="destructive"
                                disabled={processing}
                            >
                                <Trash2 />
                                {processing ? 'Menghapus...' : 'Hapus paket'}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
