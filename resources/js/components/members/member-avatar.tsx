import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

function initials(name: string) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

export function MemberAvatar({
    name,
    photoUrl,
    className,
}: {
    name: string;
    photoUrl: string | null;
    className?: string;
}) {
    return (
        <Avatar className={className}>
            {photoUrl && (
                <AvatarImage
                    src={photoUrl}
                    alt={`Foto ${name}`}
                    className="object-cover"
                />
            )}
            <AvatarFallback className="bg-primary/10 text-xs font-semibold text-primary">
                {initials(name)}
            </AvatarFallback>
        </Avatar>
    );
}
