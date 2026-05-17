import { HexColorPicker } from 'react-colorful';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { normalizeHexColor } from '@/lib/color-hex';
import { cn } from '@/lib/utils';

type ColorPickerProps = {
    value: string;
    onChange: (value: string) => void;
    disabled?: boolean;
    className?: string;
    id?: string;
};

export function ColorPicker({
    value,
    onChange,
    disabled = false,
    className,
    id,
}: ColorPickerProps) {
    const color = normalizeHexColor(value);

    const handleChange = (next: string): void => {
        onChange(normalizeHexColor(next, color));
    };

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    id={id}
                    className={cn(
                        'h-10 w-full justify-start gap-2 px-3 font-normal',
                        className,
                    )}
                >
                    <span
                        className="size-6 shrink-0 rounded-md border shadow-sm ring-1 ring-white/10"
                        style={{ backgroundColor: color }}
                        aria-hidden
                    />
                    <span className="font-mono text-xs uppercase">{color}</span>
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-3" align="start">
                <HexColorPicker
                    color={color}
                    onChange={handleChange}
                    className="color-picker-surface"
                />
                <Input
                    value={color}
                    onChange={(event) => handleChange(event.target.value)}
                    className="mt-3 font-mono text-xs uppercase"
                    maxLength={7}
                    aria-label="Hex color"
                />
            </PopoverContent>
        </Popover>
    );
}
