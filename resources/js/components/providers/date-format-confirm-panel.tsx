import { Check, Sparkles } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import type { DateFormatSuggestion } from '@/types/provider.types';

type DateFormatConfirmPanelProps = {
    suggestion: DateFormatSuggestion;
    currentFormat: string;
    confirmed: boolean;
    onConfirm: (format: string) => void;
    onDismissManual: () => void;
};

export function DateFormatConfirmPanel({
    suggestion,
    currentFormat,
    confirmed,
    onConfirm,
    onDismissManual,
}: DateFormatConfirmPanelProps) {
    const { t } = useTranslation();

    const matchesCurrent = suggestion.format === currentFormat;

    if (confirmed && matchesCurrent) {
        return (
            <div className="border-border/60 bg-muted/30 flex items-start gap-3 rounded-lg border p-4 text-sm">
                <Check className="text-primary mt-0.5 size-4 shrink-0" />
                <p>
                    {t('providers.date_format_confirmed', {
                        label: suggestion.label,
                        format: suggestion.format,
                        matched: suggestion.matched,
                        total: suggestion.total,
                    })}
                </p>
            </div>
        );
    }

    return (
        <div className="border-primary/30 bg-primary/5 flex flex-col gap-3 rounded-lg border p-4 text-sm">
            <div className="flex items-start gap-3">
                <Sparkles className="text-primary mt-0.5 size-4 shrink-0" />
                <div className="space-y-1">
                    <p className="font-medium">{t('providers.date_format_detected_title')}</p>
                    <p className="text-muted-foreground">
                        {t('providers.date_format_detected_body', {
                            label: suggestion.label,
                            format: suggestion.format,
                            matched: suggestion.matched,
                            total: suggestion.total,
                            percent: Math.round(suggestion.confidence * 100),
                        })}
                    </p>
                </div>
            </div>
            <div className="flex flex-wrap gap-2">
                <Button type="button" size="sm" onClick={() => onConfirm(suggestion.format)}>
                    {t('providers.date_format_confirm')}
                </Button>
                <Button type="button" size="sm" variant="outline" onClick={onDismissManual}>
                    {t('providers.date_format_keep_manual')}
                </Button>
            </div>
        </div>
    );
}
