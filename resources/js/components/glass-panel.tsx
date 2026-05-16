import type { HTMLAttributes } from 'react';

import { cn } from '@/lib/utils';

/**
 * Frosted panel (Mica / liquid glass). Use on sidebar, header, KPI cards — not dense tables.
 */
export function GlassPanel({
    className,
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    return (
        <div className={cn('glass rounded-xl', className)} {...props} />
    );
}
