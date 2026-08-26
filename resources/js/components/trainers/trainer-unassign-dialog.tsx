import { Form } from '@inertiajs/react';
import { UserMinus } from 'lucide-react';
import RemoveTrainerMemberController from '@/actions/App/Http/Controllers/RemoveTrainerMemberController';
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

export function TrainerUnassignDialog({
    trainerId,
    memberId,
    memberName,
}: {
    trainerId: number;
    memberId: number;
    memberName: string;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    aria-label={`Lepas assignment ${memberName}`}
                >
                    <UserMinus />
                    <span className="hidden sm:inline">Lepas</span>
                </Button>
            </DialogTrigger>
            <DialogContent className="rounded-lg shadow-md">
                <DialogHeader>
                    <DialogTitle>Lepas assignment member?</DialogTitle>
                    <DialogDescription>
                        {memberName} tidak lagi muncul pada workspace trainer.
                        Data member dan membership tidak berubah.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...RemoveTrainerMemberController.form({
                        trainer: trainerId,
                        member: memberId,
                    })}
                    options={{ preserveScroll: true }}
                >
                    {({ errors, processing }) => (
                        <div className="grid gap-4">
                            {errors.member && (
                                <p className="text-sm text-destructive">
                                    {errors.member}
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
                                    <UserMinus />
                                    {processing
                                        ? 'Melepas...'
                                        : 'Lepas assignment'}
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
