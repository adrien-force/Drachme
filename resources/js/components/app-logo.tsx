import { Coins } from 'lucide-react';

import { SidebarIconBox } from '@/components/sidebar-icon-box';
import { useSidebar } from '@/components/ui/sidebar';
import { useTranslation } from '@/hooks/use-translation';

export default function AppLogo() {
    const { state, isMobile } = useSidebar();
    const { t } = useTranslation();
    const iconOnly = state === 'collapsed' && !isMobile;

    if (iconOnly) {
        return (
            <span className="flex size-full items-center justify-center">
                <SidebarIconBox
                    icon={Coins}
                    className="bg-primary/15 text-primary ring-1 ring-primary/25"
                    iconClassName="size-[18px]"
                />
            </span>
        );
    }

    return (
        <div className="flex min-w-0 items-center gap-3">
            <SidebarIconBox
                icon={Coins}
                className="size-9 rounded-xl bg-primary/15 text-primary ring-1 ring-primary/25"
                iconClassName="size-5"
            />
            <span className="flex min-w-0 flex-col leading-tight">
                <span className="truncate text-sm font-semibold tracking-tight">
                    {t('app.name')}
                </span>
                <span className="text-muted-foreground truncate text-[11px]">
                    {t('app.tagline')}
                </span>
            </span>
        </div>
    );
}
