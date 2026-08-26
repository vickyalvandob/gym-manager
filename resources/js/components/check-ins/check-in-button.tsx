import { Form } from '@inertiajs/react';
import { LogIn } from 'lucide-react';
import CheckInController from '@/actions/App/Http/Controllers/CheckInController';
import { Button } from '@/components/ui/button';

export function CheckInButton({
    memberId,
    disabled = false,
    disabledReason,
    compact = false,
    variant = 'default',
}: {
    memberId: number;
    disabled?: boolean;
    disabledReason?: string | null;
    compact?: boolean;
    variant?: 'default' | 'outline' | 'ghost';
}) {
    if (disabled) {
        return (
            <Button
                type="button"
                size="sm"
                variant={variant}
                disabled
                title={disabledReason ?? 'Check-in belum tersedia'}
            >
                <LogIn />
                <span className={compact ? 'sr-only' : ''}>Check-in</span>
            </Button>
        );
    }

    return (
        <Form
            {...CheckInController.store.form(memberId)}
            errorBag={`check-in-${memberId}`}
            options={{ preserveScroll: true }}
        >
            {({ errors, processing }) => (
                <div className="grid justify-items-end gap-1.5">
                    <Button
                        type="submit"
                        size="sm"
                        variant={variant}
                        disabled={processing}
                        data-test={`check-in-member-${memberId}`}
                    >
                        <LogIn />
                        <span className={compact ? 'sr-only' : ''}>
                            {processing ? 'Mencatat...' : 'Check-in'}
                        </span>
                    </Button>
                    {errors.check_in && (
                        <p className="max-w-64 text-right text-xs text-destructive">
                            {errors.check_in}
                        </p>
                    )}
                </div>
            )}
        </Form>
    );
}
