import { ColorPicker } from '@/components/ui/color-picker';
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
            <div className={cn('w-full shrink-0 sm:w-48')}>
                <ColorPicker id={id} value={value} onChange={onChange} />
            </div>
        </div>
    );
}
