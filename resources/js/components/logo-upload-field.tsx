import { useEffect, useRef, useState } from 'react';

import { EntityLogo } from '@/components/entity-logo';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';

type LogoUploadFieldProps = {
    name: string;
    currentLogoUrl?: string | null;
    disabled?: boolean;
    /** When true, adds name="logo" for Inertia/HTML forms with multipart. */
    useNativeFormFields?: boolean;
    onFileChange?: (file: File | null) => void;
    onRemoveChange?: (remove: boolean) => void;
};

export function LogoUploadField({
    name,
    currentLogoUrl,
    disabled = false,
    useNativeFormFields = true,
    onFileChange,
    onRemoveChange,
}: LogoUploadFieldProps) {
    const { t } = useTranslation();
    const inputRef = useRef<HTMLInputElement>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [removeLogo, setRemoveLogo] = useState(false);

    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    const displayUrl = removeLogo ? null : (previewUrl ?? currentLogoUrl ?? null);

    const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        setPreviewUrl(file ? URL.createObjectURL(file) : null);
        setRemoveLogo(false);
        onRemoveChange?.(false);
        onFileChange?.(file);

        // Keep the file on the native input so multipart forms actually submit it.
        if (!useNativeFormFields) {
            event.target.value = '';
        }
    };

    const handleRemove = () => {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        setPreviewUrl(null);
        setRemoveLogo(true);
        onFileChange?.(null);
        onRemoveChange?.(true);

        if (inputRef.current) {
            inputRef.current.value = '';
        }
    };

    return (
        <div className="flex flex-col gap-3">
            <Label>{t('common.logo')}</Label>
            <div className="flex flex-wrap items-center gap-4">
                <EntityLogo name={name || '?'} logoUrl={displayUrl} className="size-14" />
                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={disabled}
                        onClick={() => inputRef.current?.click()}
                    >
                        {t('common.logo_choose')}
                    </Button>
                    {(displayUrl || currentLogoUrl) && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            disabled={disabled}
                            onClick={handleRemove}
                        >
                            {t('common.logo_remove')}
                        </Button>
                    )}
                </div>
            </div>
            <p className="text-muted-foreground text-xs">{t('common.logo_hint')}</p>
            <input
                ref={inputRef}
                type="file"
                {...(useNativeFormFields ? { name: 'logo' } : {})}
                accept="image/jpeg,image/png,image/webp"
                className="sr-only"
                disabled={disabled}
                onChange={handleFileChange}
            />
            {useNativeFormFields && removeLogo && (
                <input type="hidden" name="remove_logo" value="1" />
            )}
        </div>
    );
}
