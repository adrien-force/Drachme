import { useEffect, useState } from 'react';
import { RotateCcw } from 'lucide-react';

import { ColorPickerField } from '@/components/settings/color-picker-field';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
    resetThemeColorsToDefaults,
    saveThemeColors,
    useThemeColors,
} from '@/hooks/use-theme-colors';
import { useTranslation } from '@/hooks/use-translation';
import type { ThemeColorKey, ThemeColorMap } from '@/types/theme.types';

const COLOR_FIELDS: Array<{
    key: ThemeColorKey;
    labelKey: string;
    descriptionKey: string;
}> = [
    {
        key: 'primary',
        labelKey: 'settings.colors_primary',
        descriptionKey: 'settings.colors_primary_hint',
    },
    {
        key: 'chart_income',
        labelKey: 'settings.colors_income',
        descriptionKey: 'settings.colors_income_hint',
    },
    {
        key: 'chart_expense',
        labelKey: 'settings.colors_expense',
        descriptionKey: 'settings.colors_expense_hint',
    },
    {
        key: 'chart_net_worth',
        labelKey: 'settings.colors_net_worth',
        descriptionKey: 'settings.colors_net_worth_hint',
    },
    {
        key: 'chart_secondary',
        labelKey: 'settings.colors_secondary',
        descriptionKey: 'settings.colors_secondary_hint',
    },
];

export function ThemeColorsForm() {
    const { t } = useTranslation();
    const { colors, defaults, previewColors, resetPreview } = useThemeColors();
    const [draft, setDraft] = useState<ThemeColorMap>(colors);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        setDraft(colors);
    }, [colors]);

    const updateColor = (key: ThemeColorKey, value: string) => {
        const next = { ...draft, [key]: value };
        setDraft(next);
        previewColors(next);
    };

    const handleSave = () => {
        setSaving(true);
        saveThemeColors(draft, () => setSaving(false));
    };

    const handleReset = () => {
        setDraft(defaults);
        resetPreview();
        resetThemeColorsToDefaults();
    };

    return (
        <div className="space-y-6">
            <div className="space-y-4">
                {COLOR_FIELDS.map((field) => (
                    <ColorPickerField
                        key={field.key}
                        id={`theme-${field.key}`}
                        label={t(field.labelKey)}
                        description={t(field.descriptionKey)}
                        value={draft[field.key]}
                        onChange={(value) => updateColor(field.key, value)}
                    />
                ))}
            </div>

            <Separator />

            <div className="flex flex-wrap gap-3">
                <Button
                    type="button"
                    onClick={handleSave}
                    disabled={saving}
                    data-test="save-theme-colors-button"
                >
                    {t('settings.colors_save')}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    onClick={handleReset}
                    data-test="reset-theme-colors-button"
                >
                    <RotateCcw className="mr-2 size-4" />
                    {t('settings.colors_reset')}
                </Button>
            </div>
        </div>
    );
}
