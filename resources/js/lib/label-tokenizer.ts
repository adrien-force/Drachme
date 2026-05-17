/**
 * Split a bank label into selectable tokens (whitespace-separated words).
 */
export function tokenizeLabel(label: string): string[] {
    const trimmed = label.trim();
    if (trimmed === '') {
        return [];
    }

    return trimmed.split(/\s+/).filter(Boolean);
}

export function patternFromTokens(tokens: string[]): string {
    return tokens
        .map((token) => token.trim().toLowerCase())
        .filter((token) => token !== '')
        .join(' ');
}
