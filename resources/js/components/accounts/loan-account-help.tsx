import { Info } from 'lucide-react';

import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { useTranslation } from '@/hooks/use-translation';

export function LoanAccountHelp() {
    const { t } = useTranslation();

    return (
        <Collapsible className="rounded-lg border border-border/60 bg-muted/30 px-3 py-2">
            <CollapsibleTrigger className="flex w-full items-center gap-2 text-left text-sm font-medium">
                <Info className="text-muted-foreground size-4 shrink-0" />
                {t('accounts.loan_help.toggle')}
            </CollapsibleTrigger>
            <CollapsibleContent className="text-muted-foreground pt-2 text-xs leading-relaxed">
                <ol className="list-decimal space-y-2 pl-4">
                    <li>{t('accounts.loan_help.step_1')}</li>
                    <li>{t('accounts.loan_help.step_2')}</li>
                    <li>{t('accounts.loan_help.step_3')}</li>
                </ol>
            </CollapsibleContent>
        </Collapsible>
    );
}
