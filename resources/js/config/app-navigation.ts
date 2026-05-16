import {
    ArrowLeftRight,
    FileUp,
    Landmark,
    LayoutGrid,
    Plug,
    Settings,
    TrendingUp,
    type LucideIcon,
} from 'lucide-react';

import { dashboard } from '@/routes';
import { edit as editProfile } from '@/routes/profile';

export type AppNavItem = {
    titleKey: string;
    href: string;
    icon: LucideIcon;
};

export type AppNavSection = {
    labelKey: string;
    items: AppNavItem[];
};

export const appNavSections: AppNavSection[] = [
    {
        labelKey: 'nav.overview',
        items: [
            {
                titleKey: 'nav.dashboard',
                href: dashboard.url(),
                icon: LayoutGrid,
            },
        ],
    },
    {
        labelKey: 'nav.finance',
        items: [
            {
                titleKey: 'nav.accounts',
                href: '/accounts',
                icon: Landmark,
            },
            {
                titleKey: 'nav.transactions',
                href: '/transactions',
                icon: ArrowLeftRight,
            },
        ],
    },
    {
        labelKey: 'nav.data',
        items: [
            {
                titleKey: 'nav.providers',
                href: '/providers',
                icon: Plug,
            },
            {
                titleKey: 'nav.import',
                href: '/import',
                icon: FileUp,
            },
        ],
    },
    {
        labelKey: 'nav.investments_section',
        items: [
            {
                titleKey: 'nav.investments',
                href: '/investments',
                icon: TrendingUp,
            },
        ],
    },
    {
        labelKey: 'nav.account',
        items: [
            {
                titleKey: 'nav.settings',
                href: editProfile.url(),
                icon: Settings,
            },
        ],
    },
];
