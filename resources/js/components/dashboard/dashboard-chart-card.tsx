import type { ReactNode } from 'react';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

/** Fills the card body; height comes from the dashboard viewport grid. */
export const DASHBOARD_CHART_PLOT_CLASS = 'h-full min-h-0 w-full flex-1';

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
        <Card
            className={cn(
                'flex h-full min-h-0 flex-col gap-2 overflow-hidden py-3',
                className,
            )}
        >
            <CardHeader className="min-h-0 shrink-0 gap-1 px-4 pb-0 md:px-5">
                <CardTitle className="text-base md:text-lg">{title}</CardTitle>
                {hasSubtitle ? (
                    <CardDescription className="text-xs leading-snug">
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
            <CardContent className="flex min-h-0 flex-1 flex-col px-4 pb-3 md:px-5">
                {children}
            </CardContent>
        </Card>
    );
}
