import { Form } from '@inertiajs/react';
import { KeyRound, Mail } from 'lucide-react';
import { useState } from 'react';
import ActivateMemberPortalController from '@/actions/App/Http/Controllers/ActivateMemberPortalController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

type AccessMethod = 'invitation' | 'password';

export function MemberPortalAccessDialog({
    memberId,
    memberName,
    email,
    enabled,
    passwordRules,
}: {
    memberId: number;
    memberName: string;
    email: string | null;
    enabled: boolean;
    passwordRules: string;
}) {
    const [open, setOpen] = useState(false);
    const [accessMethod, setAccessMethod] =
        useState<AccessMethod>('invitation');

    const handleOpenChange = (nextOpen: boolean) => {
        setOpen(nextOpen);

        if (!nextOpen) {
            setAccessMethod('invitation');
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={!email}
                    title={
                        email
                            ? undefined
                            : 'Tambahkan email member terlebih dahulu'
                    }
                >
                    <KeyRound />
                    {enabled ? 'Kelola Portal' : 'Aktifkan Portal'}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {enabled
                            ? 'Kelola akses portal'
                            : 'Aktifkan portal member'}
                    </DialogTitle>
                    <DialogDescription>
                        Pilih kirim undangan agar member membuat password
                        sendiri, atau tetapkan password langsung.
                    </DialogDescription>
                </DialogHeader>

                <div className="rounded-md border bg-muted/30 p-4">
                    <p className="text-sm font-medium">{memberName}</p>
                    <p className="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                        <Mail className="size-4" />
                        {email}
                    </p>
                </div>

                <Form
                    {...ActivateMemberPortalController.form(memberId)}
                    errorBag={`member-portal-${memberId}`}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['password', 'password_confirmation']}
                    onSuccess={() => handleOpenChange(false)}
                >
                    {({ clearErrors, errors, processing }) => (
                        <div className="grid gap-4">
                            <input
                                type="hidden"
                                name="access_method"
                                value={accessMethod}
                            />

                            <div className="grid grid-cols-2 gap-2 rounded-lg bg-muted p-1">
                                <button
                                    type="button"
                                    aria-pressed={accessMethod === 'invitation'}
                                    className={`rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                                        accessMethod === 'invitation'
                                            ? 'bg-background text-foreground shadow-xs'
                                            : 'text-muted-foreground hover:text-foreground'
                                    }`}
                                    onClick={() => {
                                        setAccessMethod('invitation');
                                        clearErrors();
                                    }}
                                >
                                    <Mail className="mx-auto mb-1 size-4" />
                                    Kirim Undangan
                                </button>
                                <button
                                    type="button"
                                    aria-pressed={accessMethod === 'password'}
                                    className={`rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                                        accessMethod === 'password'
                                            ? 'bg-background text-foreground shadow-xs'
                                            : 'text-muted-foreground hover:text-foreground'
                                    }`}
                                    onClick={() => {
                                        setAccessMethod('password');
                                        clearErrors();
                                    }}
                                >
                                    <KeyRound className="mx-auto mb-1 size-4" />
                                    {enabled
                                        ? 'Reset Password'
                                        : 'Tetapkan Password'}
                                </button>
                            </div>

                            {accessMethod === 'invitation' ? (
                                <div className="rounded-md border border-dashed p-3 text-xs leading-5 text-muted-foreground">
                                    Tautan pembuatan password akan dikirim ke{' '}
                                    <span className="font-medium text-foreground">
                                        {email}
                                    </span>
                                    . Member menentukan passwordnya sendiri.
                                </div>
                            ) : (
                                <div className="grid gap-4 rounded-md border p-4">
                                    <p className="text-xs leading-5 text-muted-foreground">
                                        {enabled
                                            ? 'Password lama akan diganti dan sesi login member akan dihentikan.'
                                            : 'Berikan password awal ini kepada member secara aman. Member dapat menggantinya setelah login.'}
                                    </p>
                                    <div className="grid gap-2">
                                        <Label htmlFor="portal_password">
                                            {enabled
                                                ? 'Password baru'
                                                : 'Password awal'}
                                        </Label>
                                        <PasswordInput
                                            id="portal_password"
                                            name="password"
                                            autoComplete="new-password"
                                            passwordrules={passwordRules}
                                        />
                                        <InputError message={errors.password} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="portal_password_confirmation">
                                            Konfirmasi password
                                        </Label>
                                        <PasswordInput
                                            id="portal_password_confirmation"
                                            name="password_confirmation"
                                            autoComplete="new-password"
                                            passwordrules={passwordRules}
                                        />
                                        <InputError
                                            message={
                                                errors.password_confirmation
                                            }
                                        />
                                    </div>
                                </div>
                            )}

                            <InputError message={errors.portal_access} />
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => handleOpenChange(false)}
                                >
                                    Batal
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Memproses...'
                                        : accessMethod === 'invitation'
                                          ? 'Kirim Undangan'
                                          : enabled
                                            ? 'Reset Password'
                                            : 'Aktifkan dengan Password'}
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
