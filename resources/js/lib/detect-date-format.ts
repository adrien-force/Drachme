import type { DateFormatSuggestion } from '@/types/provider.types';

type DateFormatCandidate = {
    format: string;
    label: string;
    pattern: RegExp;
};

/** Mirrors backend DateFormatDetector candidates (order = priority). */
const CANDIDATES: DateFormatCandidate[] = [
    {
        format: 'Y-m-d H:i:s',
        label: 'AAAA-MM-JJ HH:MM:SS',
        pattern: /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/,
    },
    {
        format: 'Y-m-d H:i',
        label: 'AAAA-MM-JJ HH:MM',
        pattern: /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})$/,
    },
    {
        format: 'Y-m-d',
        label: 'AAAA-MM-JJ',
        pattern: /^(\d{4})-(\d{2})-(\d{2})$/,
    },
    {
        format: 'd/m/Y H:i:s',
        label: 'JJ/MM/AAAA HH:MM:SS',
        pattern: /^(\d{1,2})\/(\d{1,2})\/(\d{4}) (\d{1,2}):(\d{2}):(\d{2})$/,
    },
    {
        format: 'd/m/Y H:i',
        label: 'JJ/MM/AAAA HH:MM',
        pattern: /^(\d{1,2})\/(\d{1,2})\/(\d{4}) (\d{1,2}):(\d{2})$/,
    },
    {
        format: 'd/m/Y',
        label: 'JJ/MM/AAAA',
        pattern: /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/,
    },
    {
        format: 'd-m-Y H:i:s',
        label: 'JJ-MM-AAAA HH:MM:SS',
        pattern: /^(\d{1,2})-(\d{1,2})-(\d{4}) (\d{1,2}):(\d{2}):(\d{2})$/,
    },
    {
        format: 'd-m-Y',
        label: 'JJ-MM-AAAA',
        pattern: /^(\d{1,2})-(\d{1,2})-(\d{4})$/,
    },
    {
        format: 'd.m.Y H:i:s',
        label: 'JJ.MM.AAAA HH:MM:SS',
        pattern: /^(\d{1,2})\.(\d{1,2})\.(\d{4}) (\d{1,2}):(\d{2}):(\d{2})$/,
    },
    {
        format: 'd.m.Y',
        label: 'JJ.MM.AAAA',
        pattern: /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/,
    },
    {
        format: 'Y/m/d H:i:s',
        label: 'AAAA/MM/JJ HH:MM:SS',
        pattern: /^(\d{4})\/(\d{2})\/(\d{2}) (\d{1,2}):(\d{2}):(\d{2})$/,
    },
    {
        format: 'Y/m/d',
        label: 'AAAA/MM/JJ',
        pattern: /^(\d{4})\/(\d{2})\/(\d{2})$/,
    },
    {
        format: 'm/d/Y H:i:s',
        label: 'MM/JJ/AAAA HH:MM:SS',
        pattern: /^(\d{1,2})\/(\d{1,2})\/(\d{4}) (\d{1,2}):(\d{2}):(\d{2})$/,
    },
    {
        format: 'm/d/Y',
        label: 'MM/JJ/AAAA',
        pattern: /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/,
    },
    {
        format: 'd/m/y H:i:s',
        label: 'JJ/MM/AA HH:MM:SS',
        pattern: /^(\d{1,2})\/(\d{1,2})\/(\d{2}) (\d{1,2}):(\d{2}):(\d{2})$/,
    },
    {
        format: 'd/m/y',
        label: 'JJ/MM/AA',
        pattern: /^(\d{1,2})\/(\d{1,2})\/(\d{2})$/,
    },
];

function matchesFormat(value: string, pattern: RegExp): boolean {
    return pattern.test(value.trim());
}

export function detectDateFormat(samples: string[]): DateFormatSuggestion | null {
    const values = samples
        .map((sample) => sample.trim())
        .filter((sample) => sample.length > 0);

    if (values.length === 0) {
        return null;
    }

    let best: DateFormatCandidate | null = null;
    let bestMatched = 0;

    for (const candidate of CANDIDATES) {
        let matched = 0;

        for (const value of values) {
            if (matchesFormat(value, candidate.pattern)) {
                matched++;
            }
        }

        if (matched > bestMatched) {
            bestMatched = matched;
            best = candidate;
        }
    }

    if (best === null || bestMatched === 0) {
        return null;
    }

    return {
        format: best.format,
        label: best.label,
        matched: bestMatched,
        total: values.length,
        confidence: Math.round((bestMatched / values.length) * 100) / 100,
    };
}
