import { cn } from '@/lib/utils';

type CategoryBadgeProps = {
    name: string;
    color?: string | null;
    className?: string;
};

export function CategoryBadge({ name, color, className }: CategoryBadgeProps) {
    const accent = color ?? 'var(--muted-foreground)';

    return (
        <span
            className={cn(
                'inline-flex max-w-[12rem] truncate rounded-full px-2 py-0.5 text-xs font-medium',
                className,
            )}
            style={{
                backgroundColor: `color-mix(in oklab, ${accent} 18%, transparent)`,
                color: accent,
            }}
            title={name}
        >
            {name}
        </span>
    );
}
