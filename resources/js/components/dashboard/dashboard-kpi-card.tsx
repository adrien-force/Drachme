import type { ReactNode } from 'react';

import { GlassPanel } from '@/components/glass-panel';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

type DashboardKpiCardProps = {
    label: string;
    value: ReactNode;
    footnote: ReactNode;
    valueClassName?: string;
};

export function DashboardKpiCard({
    label,
    value,
    footnote,
    valueClassName,
}: DashboardKpiCardProps) {
    return (
        <GlassPanel className="flex h-full flex-col overflow-hidden p-0">
            <Card className="flex h-full min-h-0 flex-col border-0 bg-transparent px-4 py-2.5 shadow-none md:px-5 md:py-3">
                <div className="shrink-0">
                    <CardHeader className="gap-0.5 p-0">
                        <CardDescription className="text-xs">{label}</CardDescription>
                        <CardTitle
                            className={cn(
                                'text-lg leading-tight tabular-nums md:text-xl',
                                valueClassName,
                            )}
                        >
                            {value}
                        </CardTitle>
                    </CardHeader>
                    <div className="pt-1.5 text-sm leading-snug">{footnote}</div>
                </div>
                <div aria-hidden className="min-h-0 flex-1" />
            </Card>
        </GlassPanel>
    );
}
