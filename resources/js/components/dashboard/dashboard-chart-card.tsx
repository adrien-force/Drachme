import type { ReactNode } from 'react';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

/** Shared plot area height so dashboard chart cards align in the grid. */
export const DASHBOARD_CHART_PLOT_CLASS = 'min-h-72 w-full flex-1';

type DashboardChartCardProps = {
    title: string;
    description?: string;
    hint?: string;
    children: ReactNode;
    className?: string;
};

export function DashboardChartCard({
    title,
    description,
    hint,
    children,
    className,
}: DashboardChartCardProps) {
    const hasSubtitle = description !== undefined || hint !== undefined;

    return (
        <Card className={cn('flex h-full flex-col', className)}>
            <CardHeader className="min-h-[4.75rem] shrink-0">
                <CardTitle>{title}</CardTitle>
                {hasSubtitle ? (
                    <CardDescription>
                        {description ?? <span aria-hidden>{'\u00a0'}</span>}
                        {hint ? (
                            <span className="text-muted-foreground/90 mt-1 block text-xs">
                                {hint}
                            </span>
                        ) : null}
                    </CardDescription>
                ) : (
                    <CardDescription className="sr-only" aria-hidden>
                        {'\u00a0'}
                    </CardDescription>
                )}
            </CardHeader>
            <CardContent className="flex min-h-0 flex-1 flex-col">{children}</CardContent>
        </Card>
    );
}
