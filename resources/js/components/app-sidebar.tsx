import { Link } from '@inertiajs/react';

import AppLogo from '@/components/app-logo';
import {
    DrachmeNavLink,
    DrachmeNavSection,
} from '@/components/layout/drachme-nav-link';
import { NavUser } from '@/components/nav-user';
import { appNavSections } from '@/config/app-navigation';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from '@/components/ui/sidebar';
import {
    sidebarCollapsedButtonClass,
    sidebarCollapsedMenuItemClass,
    sidebarCollapsedSectionClass,
} from '@/lib/sidebar-classes';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

export function AppSidebar() {
    return (
        <Sidebar
            collapsible="icon"
            className={cn(
                'border-0 bg-transparent shadow-none',
                '[&_[data-sidebar=sidebar]]:drachme-sidebar-panel',
                '[&_[data-sidebar=sidebar]]:border-border/30',
                '[&_[data-sidebar=sidebar]]:border-r',
                '[&_[data-sidebar=sidebar]]:bg-transparent',
                '[&_[data-sidebar=sidebar]]:shadow-none',
            )}
        >
            <SidebarHeader
                className={cn(
                    'border-border/30 border-b px-3 py-4',
                    sidebarCollapsedSectionClass,
                    'group-data-[collapsible=icon]:py-2',
                )}
            >
                <SidebarMenu className="group-data-[collapsible=icon]:w-full">
                    <SidebarMenuItem className={sidebarCollapsedMenuItemClass}>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className={cn(
                                'h-auto bg-transparent px-2 py-1 hover:bg-transparent active:bg-transparent',
                                sidebarCollapsedButtonClass,
                            )}
                        >
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="gap-4 px-1 py-4 group-data-[collapsible=icon]:px-0 group-data-[collapsible=icon]:py-3">
                {appNavSections.map((section) => (
                    <DrachmeNavSection
                        key={section.labelKey}
                        labelKey={section.labelKey}
                    >
                        {section.items.map((item) => (
                            <DrachmeNavLink
                                key={item.href}
                                titleKey={item.titleKey}
                                href={item.href}
                                icon={item.icon}
                            />
                        ))}
                    </DrachmeNavSection>
                ))}
            </SidebarContent>

            <SidebarFooter
                className={cn(
                    'border-border/30 border-t p-2',
                    sidebarCollapsedSectionClass,
                    'group-data-[collapsible=icon]:py-2',
                )}
            >
                <NavUser />
            </SidebarFooter>

            <SidebarRail className="hover:after:bg-primary/40" />
        </Sidebar>
    );
}
