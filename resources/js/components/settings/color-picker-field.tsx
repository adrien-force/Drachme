import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type ColorPickerFieldProps = {
    id: string;
    label: string;
    description?: string;
    value: string;
    onChange: (value: string) => void;
};

export function ColorPickerField({
    id,
    label,
    description,
    value,
    onChange,
}: ColorPickerFieldProps) {
    return (
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div className="min-w-0 flex-1 space-y-1">
                <Label htmlFor={id}>{label}</Label>
                {description && (
                    <p className="text-muted-foreground text-sm">{description}</p>
                )}
            </div>
            <div className="flex shrink-0 items-center gap-3">
                <span
                    className={cn(
                        'size-10 shrink-0 rounded-lg border shadow-sm',
                        'ring-1 ring-white/10',
                    )}
                    style={{ backgroundColor: value }}
                    aria-hidden
                />
                <Input
                    id={id}
                    type="color"
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    className="h-10 w-14 cursor-pointer border-0 p-1"
                />
                <span className="text-muted-foreground w-[4.5rem] font-mono text-xs uppercase">
                    {value}
                </span>
            </div>
        </div>
    );
}
