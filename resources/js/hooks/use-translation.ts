import { usePage } from '@inertiajs/react';

type TranslationTree = Record<string, string | TranslationTree>;

function resolveTranslation(
    tree: TranslationTree,
    key: string,
): string | undefined {
    const parts = key.split('.');
    let current: string | TranslationTree | undefined = tree;

    for (const part of parts) {
        if (typeof current !== 'object' || current === null || !(part in current)) {
            return undefined;
        }

        current = current[part];
    }

    return typeof current === 'string' ? current : undefined;
}

function interpolate(
    template: string,
    params?: Record<string, string | number>,
): string {
    if (!params) {
        return template;
    }

    return Object.entries(params).reduce(
        (text, [name, value]) =>
            text.replaceAll(`:${name}`, String(value)),
        template,
    );
}

export function useTranslation() {
    const { locale, translations } = usePage().props;

    const t = (key: string, params?: Record<string, string | number>): string => {
        const tree = translations as TranslationTree;
        const value = resolveTranslation(tree, key);

        if (value === undefined) {
            return key;
        }

        return interpolate(value, params);
    };

    return { t, locale: locale as string };
}
