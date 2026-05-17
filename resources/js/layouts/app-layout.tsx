import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

export default function AppLayout({
    breadcrumbs = [],
    headerEnd,
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    headerEnd?: React.ReactNode;
    children: React.ReactNode;
}) {
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} headerEnd={headerEnd}>
            {children}
        </AppLayoutTemplate>
    );
}
