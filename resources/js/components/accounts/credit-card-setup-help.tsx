import { ChevronDown, CircleHelp } from 'lucide-react';

import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

const STEP_KEYS = [
    'accounts.credit_card_help.step_1',
    'accounts.credit_card_help.step_2',
    'accounts.credit_card_help.step_3',
    'accounts.credit_card_help.step_4',
    'accounts.credit_card_help.step_5',
] as const;

type CreditCardSetupHelpProps = {
    className?: string;
};

export function CreditCardSetupHelp({ className }: CreditCardSetupHelpProps) {
    const { t } = useTranslation();

    return (
        <Collapsible
            className={cn(
                'rounded-lg border border-border/60 bg-muted/20 text-sm',
                className,
            )}
        >
            <div className="flex items-start gap-2 px-3 py-2.5">
                <TooltipProvider delayDuration={200}>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <button
                                type="button"
                                className="text-muted-foreground hover:text-foreground mt-0.5 shrink-0 rounded-md p-0.5 transition-colors"
                                aria-label={t('accounts.credit_card_help.tooltip_aria')}
                            >
                                <CircleHelp className="size-4" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent
                            side="top"
                            className="bg-popover text-popover-foreground max-w-xs text-left leading-snug"
                        >
                            {t('accounts.credit_card_help.tooltip')}
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
                <div className="min-w-0 flex-1 space-y-1">
                    <p className="text-muted-foreground text-xs leading-relaxed">
                        {t('accounts.credit_card_help.short')}
                    </p>
                    <CollapsibleTrigger className="group text-primary hover:text-primary/90 inline-flex items-center gap-1 text-xs font-medium">
                        <ChevronDown className="size-3.5 transition-transform group-data-[state=open]:rotate-180" />
                        {t('accounts.credit_card_help.toggle')}
                    </CollapsibleTrigger>
                </div>
            </div>
            <CollapsibleContent>
                <ol className="text-muted-foreground list-decimal space-y-2 border-t border-border/50 px-3 py-3 pl-8 text-xs leading-relaxed">
                    {STEP_KEYS.map((key) => (
                        <li key={key}>{t(key)}</li>
                    ))}
                </ol>
            </CollapsibleContent>
        </Collapsible>
    );
}
