import { usePage } from '@inertiajs/react';
import { ChevronsUpDown } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { useIsMobile } from '@/hooks/use-mobile';
import {
    sidebarCollapsedButtonClass,
    sidebarCollapsedMenuItemClass,
} from '@/lib/sidebar-classes';
import { cn } from '@/lib/utils';

export function NavUser() {
    const { auth } = usePage().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();
    const iconOnly = state === 'collapsed' && !isMobile;

    if (!auth.user) {
        return null;
    }

    const trigger = (
        <SidebarMenuButton
            size="lg"
            className={cn(
                'hover:bg-foreground/5 data-[state=open]:bg-foreground/8 h-auto rounded-xl px-2 py-2',
                'group-data-[collapsible=icon]:rounded-lg',
                sidebarCollapsedButtonClass,
            )}
            data-test="sidebar-menu-button"
        >
            <UserInfo user={auth.user} iconOnly={iconOnly} />
            {!iconOnly && <ChevronsUpDown className="ml-auto size-4" />}
        </SidebarMenuButton>
    );

    return (
        <SidebarMenu className="group-data-[collapsible=icon]:w-full">
            <SidebarMenuItem className={sidebarCollapsedMenuItemClass}>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        {iconOnly ? (
                            <Tooltip>
                                <TooltipTrigger asChild>{trigger}</TooltipTrigger>
                                <TooltipContent side="right" align="center">
                                    {auth.user.name}
                                </TooltipContent>
                            </Tooltip>
                        ) : (
                            trigger
                        )}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="end"
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? 'left'
                                  : 'bottom'
                        }
                    >
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
