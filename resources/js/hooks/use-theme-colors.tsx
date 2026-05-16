import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import {
    applyThemeColors,
    persistThemeColors,
} from '@/lib/theme-colors';
import type { ThemeColorMap, ThemeSharedProps } from '@/types/theme.types';

export function useThemeColors() {
    const { theme } = usePage().props;
    const { colors: serverColors, defaults } = theme as ThemeSharedProps;

    const [colors, setColors] = useState<ThemeColorMap>(serverColors);

    useEffect(() => {
        setColors(serverColors);
        applyThemeColors(serverColors);
        persistThemeColors(serverColors);
    }, [serverColors]);

    const previewColors = useCallback((next: ThemeColorMap) => {
        setColors(next);
        applyThemeColors(next);
    }, []);

    const resetPreview = useCallback(() => {
        previewColors(defaults);
    }, [defaults, previewColors]);

    return {
        colors,
        defaults,
        previewColors,
        resetPreview,
        setColors,
    };
}

export function saveThemeColors(
    colors: ThemeColorMap,
    onFinish?: () => void,
): void {
    router.patch('/settings/appearance', { colors }, {
        preserveScroll: true,
        onSuccess: () => persistThemeColors(colors),
        onFinish: () => onFinish?.(),
    });
}

export function resetThemeColorsToDefaults(): void {
    router.post('/settings/appearance/reset', {}, { preserveScroll: true });
}
