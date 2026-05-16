export type ParseCsvOptions = {
    delimiter: string;
    enclosure: string;
    skipRows: number;
};

export function parseCsvSample(text: string, options: ParseCsvOptions): string[][] {
    const lines = text
        .split(/\r?\n/)
        .map((line) => line.trimEnd())
        .filter((line) => line.length > 0);

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
