import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { ThemeColorsHydrator } from '@/components/theme-colors-hydrator';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
    headerEnd,
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <ThemeColorsHydrator />
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} headerEnd={headerEnd} />
                {children}
            </AppContent>
        </AppShell>
    );
}
