const HEX_PATTERN = /^#[0-9A-Fa-f]{6}$/;

export function normalizeHexColor(value: string, fallback = '#000000'): string {
    const trimmed = value.trim();
    const withHash = trimmed.startsWith('#') ? trimmed : `#${trimmed}`;

    if (HEX_PATTERN.test(withHash)) {
        return withHash.toUpperCase();
    }

    return fallback;
}
