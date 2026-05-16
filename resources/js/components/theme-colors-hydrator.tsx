import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { applyThemeColors, persistThemeColors } from '@/lib/theme-colors';
import type { ThemeSharedProps } from '@/types/theme.types';

export function ThemeColorsHydrator() {
    const { theme } = usePage().props;

    useEffect(() => {
        const { colors } = theme as ThemeSharedProps;
        applyThemeColors(colors);
        persistThemeColors(colors);
    }, [theme]);

    return null;
}
