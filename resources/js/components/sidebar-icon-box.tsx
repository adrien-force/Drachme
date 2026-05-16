import type { LucideIcon } from 'lucide-react';

import { cn } from '@/lib/utils';

type SidebarIconBoxProps = {
    icon: LucideIcon;
    active?: boolean;
    className?: string;
    iconClassName?: string;
};

export function SidebarIconBox({
    icon: Icon,
    active = false,
    className,
    iconClassName,
}: SidebarIconBoxProps) {
    return (
        <span
            className={cn(
                'flex size-8 shrink-0 items-center justify-center rounded-lg transition-colors',
                active
                    ? 'bg-primary/20 text-primary'
                    : 'bg-foreground/5 text-muted-foreground',
                className,
            )}
        >
            <Icon
                className={cn('size-4', iconClassName)}
                strokeWidth={active ? 2.25 : 2}
            />
        </span>
    );
}
