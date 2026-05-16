import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { entityInitials } from '@/lib/entity-initials';
import { cn } from '@/lib/utils';

type EntityLogoProps = {
    name: string;
    logoUrl?: string | null;
    className?: string;
    imageClassName?: string;
};

export function EntityLogo({ name, logoUrl, className, imageClassName }: EntityLogoProps) {
    return (
        <Avatar className={cn('size-10 rounded-lg', className)}>
            {logoUrl ? (
                <AvatarImage
                    src={logoUrl}
                    alt={name}
                    className={cn('rounded-lg object-cover', imageClassName)}
                />
            ) : null}
            <AvatarFallback className="rounded-lg text-xs font-medium">
                {entityInitials(name)}
            </AvatarFallback>
        </Avatar>
    );
}
