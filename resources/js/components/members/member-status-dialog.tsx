import { Form } from '@inertiajs/react';
import { Power, PowerOff } from 'lucide-react';
import UpdateMemberStatusController from '@/actions/App/Http/Controllers/UpdateMemberStatusController';
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
import type { MemberStatus } from '@/types';

export function MemberStatusDialog({
    memberId,
    memberName,
    status,
    buttonVariant = 'outline',
}: {
    memberId: number;
    memberName: string;
    status: MemberStatus;
    buttonVariant?: 'outline' | 'ghost';
}) {
    const activating = status === 'inactive';
    const nextStatus: MemberStatus = activating ? 'active' : 'inactive';

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant={buttonVariant}
                    size="sm"
                    title={
                        activating ? 'Aktifkan member' : 'Nonaktifkan member'
                    }
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
                        {activating ? 'Aktifkan member' : 'Nonaktifkan member'}
                    </DialogTitle>
                    <DialogDescription>
                        Status {memberName} akan diubah menjadi{' '}
                        {activating ? 'aktif' : 'nonaktif'}.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...UpdateMemberStatusController.form(memberId)}
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
                                name="status"
                                value={nextStatus}
                            />
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
