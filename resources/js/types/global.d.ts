import type { Auth } from '@/types/auth';
import type { ThemeSharedProps } from '@/types/theme.types';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            locale: string;
            translations: Record<string, unknown>;
            theme: ThemeSharedProps;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
