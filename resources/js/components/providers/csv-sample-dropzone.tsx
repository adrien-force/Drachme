import { FileSpreadsheet, Upload } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

const ACCEPT = '.csv,.txt,text/csv';

type CsvSampleDropzoneProps = {
    value: string;
    onChange: (text: string) => void;
    fileName: string | null;
    onFileNameChange: (name: string | null) => void;
    disabled?: boolean;
};

async function readCsvFile(file: File): Promise<string> {
    return file.text();
}

export function CsvSampleDropzone({
    value,
    onChange,
    fileName,
    onFileNameChange,
    disabled = false,
}: CsvSampleDropzoneProps) {
    const { t } = useTranslation();
    const inputRef = useRef<HTMLInputElement>(null);
    const [dragActive, setDragActive] = useState(false);
    const [showPaste, setShowPaste] = useState(value.length > 0 && fileName === null);

    const applyFile = useCallback(
        async (file: File) => {
            const text = await readCsvFile(file);
            onChange(text);
            onFileNameChange(file.name);
            setShowPaste(false);
        },
        [onChange, onFileNameChange],
    );

    const handleFiles = useCallback(
        (files: FileList | null) => {
            const file = files?.[0];
            if (!file) {
                return;
            }

            void applyFile(file);
        },
        [applyFile],
    );

    const clearFile = () => {
        onChange('');
        onFileNameChange(null);
        if (inputRef.current) {
            inputRef.current.value = '';
        }
    };

    return (
        <div className="space-y-3">
            <div
                role="button"
                tabIndex={disabled ? -1 : 0}
                onKeyDown={(event) => {
                    if (disabled) {
                        return;
                    }

                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        inputRef.current?.click();
                    }
                }}
                onDragEnter={(event) => {
                    event.preventDefault();
                    if (!disabled) {
                        setDragActive(true);
                    }
                }}
                onDragOver={(event) => {
                    event.preventDefault();
                    if (!disabled) {
                        setDragActive(true);
                    }
                }}
                onDragLeave={(event) => {
                    event.preventDefault();
                    setDragActive(false);
                }}
                onDrop={(event) => {
                    event.preventDefault();
                    setDragActive(false);

                    if (disabled) {
                        return;
                    }

                    handleFiles(event.dataTransfer.files);
                }}
                onClick={() => {
                    if (!disabled) {
                        inputRef.current?.click();
                    }
                }}
                className={cn(
                    'border-input flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border border-dashed px-6 py-10 text-center transition-colors',
                    dragActive && 'border-primary bg-primary/5',
                    !dragActive && 'hover:border-primary/50 hover:bg-muted/30',
                    disabled && 'pointer-events-none opacity-50',
                )}
            >
                <div className="bg-muted flex size-12 items-center justify-center rounded-full">
                    <Upload className="text-muted-foreground size-5" />
                </div>
                <div className="space-y-1">
                    <p className="text-sm font-medium">{t('providers.dropzone_title')}</p>
                    <p className="text-muted-foreground text-xs">
                        {t('providers.dropzone_hint')}
                    </p>
                </div>
                <Button type="button" variant="secondary" size="sm" disabled={disabled}>
                    {t('providers.upload_file')}
                </Button>
                <input
                    ref={inputRef}
                    type="file"
                    accept={ACCEPT}
                    className="sr-only"
                    disabled={disabled}
                    onChange={(event) => handleFiles(event.target.files)}
                />
            </div>

            {fileName ? (
                <div className="bg-muted/40 flex flex-wrap items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm">
                    <div className="flex min-w-0 items-center gap-2">
                        <FileSpreadsheet className="text-muted-foreground size-4 shrink-0" />
                        <span className="truncate font-medium">{fileName}</span>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        disabled={disabled}
                        onClick={(event) => {
                            event.stopPropagation();
                            clearFile();
                        }}
                    >
                        {t('providers.dropzone_replace')}
                    </Button>
                </div>
            ) : null}

            <div className="flex items-center gap-2">
                <div className="bg-border h-px flex-1" />
                <span className="text-muted-foreground text-xs">
                    {t('providers.dropzone_or_paste')}
                </span>
                <div className="bg-border h-px flex-1" />
            </div>

            <Button
                type="button"
                variant="ghost"
                size="sm"
                className="w-fit px-0"
                onClick={() => setShowPaste((current) => !current)}
            >
                {showPaste
                    ? t('providers.dropzone_hide_paste')
                    : t('providers.dropzone_show_paste')}
            </Button>

            {showPaste ? (
                <textarea
                    value={value}
                    onChange={(event) => {
                        onChange(event.target.value);
                        onFileNameChange(null);
                    }}
                    placeholder={t('providers.sample_placeholder')}
                    rows={6}
                    disabled={disabled}
                    className={cn(
                        'border-input placeholder:text-muted-foreground w-full rounded-md border bg-transparent px-3 py-2 font-mono text-xs shadow-xs outline-none',
                        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                    )}
                />
            ) : null}
        </div>
    );
}
