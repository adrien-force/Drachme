import AppLogoIcon from '@/components/app-logo-icon';
import { useSidebar } from '@/components/ui/sidebar';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

const logoBoxClassName =
    'flex shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary ring-1 ring-primary/25';

export default function AppLogo() {
    const { state, isMobile } = useSidebar();
    const { t } = useTranslation();
    const iconOnly = state === 'collapsed' && !isMobile;

    if (iconOnly) {
        return (
            <span className="flex size-full items-center justify-center">
                <span className={cn(logoBoxClassName, 'size-8 rounded-lg')}>
                    <AppLogoIcon className="size-[18px] fill-current" />
                </span>
            </span>
        );
    }

    return (
        <div className="flex min-w-0 items-center gap-3">
            <span className={cn(logoBoxClassName, 'size-9')}>
                <AppLogoIcon className="size-5 fill-current" />
            </span>
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
