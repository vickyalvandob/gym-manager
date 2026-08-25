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
    canDelete = true,
    buttonVariant = 'outline',
}: {
    membershipPlanId: number;
    membershipPlanName: string;
    canDelete?: boolean;
    buttonVariant?: 'outline' | 'ghost';
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant={buttonVariant}
                    size="sm"
                    disabled={!canDelete}
                    title={
                        canDelete
                            ? 'Hapus paket'
                            : 'Paket sudah digunakan dan tidak dapat dihapus'
                    }
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
                    {({ errors, processing }) => (
                        <div className="grid gap-4">
                            {errors.membership_plan && (
                                <p className="text-sm text-destructive">
                                    {errors.membership_plan}
                                </p>
                            )}
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
                                    {processing
                                        ? 'Menghapus...'
                                        : 'Hapus paket'}
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
