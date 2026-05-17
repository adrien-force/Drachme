import type { ReactNode } from 'react';

import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
    headerEnd,
}: {
    breadcrumbs?: BreadcrumbItemType[];
    headerEnd?: ReactNode;
}) {
    return (
        <header className="border-border/25 bg-background/40 mx-4 mt-4 flex h-14 shrink-0 items-center gap-3 rounded-xl border px-4 backdrop-blur-sm md:mx-6">
            <SidebarTrigger className="text-muted-foreground hover:text-foreground size-8" />
            <div className="bg-border/60 hidden h-5 w-px sm:block" />
            <Breadcrumbs breadcrumbs={breadcrumbs} />
            {headerEnd ? (
                <div className="ml-auto flex shrink-0 items-center">{headerEnd}</div>
            ) : null}
        </header>
    );
}
