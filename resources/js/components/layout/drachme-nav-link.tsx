import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { SidebarIconBox } from '@/components/sidebar-icon-box';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useSidebar } from '@/components/ui/sidebar';
import { appNavHrefs } from '@/config/app-navigation';
import {
    normalizePathname,
    resolveActiveNavHref,
    useCurrentUrl,
} from '@/hooks/use-current-url';
import { useTranslation } from '@/hooks/use-translation';
import { cn, toUrl } from '@/lib/utils';

export type DrachmeNavLinkProps = {
    titleKey: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: LucideIcon;
    badge?: string;
};

export function DrachmeNavLink({
    titleKey,
    href,
    icon: Icon,
    badge,
}: DrachmeNavLinkProps) {
    const { t } = useTranslation();
    const { currentUrl } = useCurrentUrl();
    const { state, isMobile } = useSidebar();
    const title = t(titleKey);
    const activeHref = resolveActiveNavHref(currentUrl, appNavHrefs);
    const active =
        activeHref !== null &&
        normalizePathname(toUrl(href)) === normalizePathname(activeHref);
    const iconOnly = state === 'collapsed' && !isMobile;

    const link = (
        <Link
            href={href}
            prefetch
            className={cn(
                'group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-all duration-200',
                iconOnly && 'size-10 justify-center p-0',
                active
                    ? 'bg-primary/12 font-medium text-primary shadow-[inset_0_0_0_1px_oklch(from_var(--primary)_l_c_/_0.35)]'
                    : 'text-muted-foreground hover:bg-foreground/5 hover:text-foreground',
            )}
        >
            <SidebarIconBox icon={Icon} active={active} />
            {!iconOnly && <span className="truncate">{title}</span>}
            {!iconOnly && badge && (
                <span className="text-muted-foreground ml-auto rounded-md bg-foreground/5 px-1.5 py-0.5 text-[10px] font-medium tracking-wide uppercase">
                    {badge}
                </span>
            )}
            {active && !iconOnly && (
                <span className="bg-primary absolute top-1/2 left-0 h-5 w-0.5 -translate-y-1/2 rounded-full" />
            )}
        </Link>
    );

    if (!iconOnly) {
        return link;
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>{link}</TooltipTrigger>
            <TooltipContent side="right" align="center">
                {title}
            </TooltipContent>
        </Tooltip>
    );
}

export function DrachmeNavSection({
    labelKey,
    children,
}: {
    labelKey: string;
    children: ReactNode;
}) {
    const { t } = useTranslation();
    const { state, isMobile } = useSidebar();
    const iconOnly = state === 'collapsed' && !isMobile;

    return (
        <div className={cn('space-y-1.5 px-2', iconOnly && 'px-0')}>
            {!iconOnly && (
                <p className="text-muted-foreground px-3 text-[11px] font-semibold tracking-widest uppercase">
                    {t(labelKey)}
                </p>
            )}
            <nav
                className={cn(
                    'flex flex-col gap-0.5',
                    iconOnly && 'items-center gap-1',
                )}
            >
                {children}
            </nav>
        </div>
    );
}
