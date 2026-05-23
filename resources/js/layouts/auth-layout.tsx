import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';
import { useTranslation } from '@/hooks/use-translation';
import type { AuthLayoutProps } from '@/types';

export default function AuthLayout({
    title = '',
    titleKey,
    description = '',
    descriptionKey,
    children,
}: AuthLayoutProps) {
    const { t } = useTranslation();
    const resolvedTitle = titleKey !== undefined ? t(titleKey) : title;
    const resolvedDescription =
        descriptionKey !== undefined ? t(descriptionKey) : description;

    return (
        <AuthLayoutTemplate
            title={resolvedTitle}
            description={resolvedDescription}
        >
            {children}
        </AuthLayoutTemplate>
    );
}
