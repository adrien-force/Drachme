export type ParseCsvOptions = {
    delimiter: string;
    enclosure: string;
    skipRows: number;
};

function splitCsvLines(text: string): string[] {
    return text
        .split(/\r?\n/)
        .map((line) => line.trimEnd())
        .filter((line) => line.length > 0);
}

/** Header row taken from skipped lines (last skipped line when skipRows > 0). */
export function parseCsvHeaderRow(
    text: string,
    options: ParseCsvOptions,
): string[] | null {
    if (options.skipRows <= 0) {
        return null;
    }

    const lines = splitCsvLines(text);

    if (lines.length < options.skipRows) {
        return null;
    }

    return parseCsvLine(
        lines[options.skipRows - 1],
        options.delimiter,
        options.enclosure,
    );
}

export function parseCsvSample(text: string, options: ParseCsvOptions): string[][] {
    const lines = splitCsvLines(text);
    const dataLines = lines.slice(options.skipRows);

    return dataLines.map((line) => parseCsvLine(line, options.delimiter, options.enclosure));
}

function parseCsvLine(line: string, delimiter: string, enclosure: string): string[] {
    const cells: string[] = [];
    let current = '';
    let inQuotes = false;

    for (let i = 0; i < line.length; i += 1) {
        const char = line[i];
        const next = line[i + 1];

        if (char === enclosure) {
            if (inQuotes && next === enclosure) {
                current += enclosure;
                i += 1;
            } else {
                inQuotes = !inQuotes;
            }

            continue;
        }

        if (char === delimiter && !inQuotes) {
            cells.push(current.trim());
            current = '';
            continue;
        }

        current += char;
    }

    cells.push(current.trim());

    return cells;
}
