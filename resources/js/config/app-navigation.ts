import {
    ArrowLeftRight,
    FileUp,
    Landmark,
    LayoutGrid,
    ListFilter,
    Plug,
    Repeat,
    SlidersHorizontal,
    Tags,
    Sparkles,
    TrendingUp,
    type LucideIcon,
} from 'lucide-react';

import { dashboard } from '@/routes';

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
            {
                titleKey: 'nav.transaction_triage',
                href: '/transactions/triage',
                icon: Sparkles,
            },
            {
                titleKey: 'nav.transfers',
                href: '/transfers',
                icon: SlidersHorizontal,
            },
            {
                titleKey: 'nav.recurring',
                href: '/recurring',
                icon: Repeat,
            },
        ],
    },
    {
        labelKey: 'nav.data',
        items: [
            {
                titleKey: 'nav.import',
                href: '/import',
                icon: FileUp,
            },
        ],
    },
    {
        labelKey: 'nav.configuration',
        items: [
            {
                titleKey: 'nav.categories',
                href: '/categories',
                icon: Tags,
            },
            {
                titleKey: 'nav.category_rules',
                href: '/category-rules',
                icon: ListFilter,
            },
            {
                titleKey: 'nav.providers',
                href: '/providers',
                icon: Plug,
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
];

export const appNavHrefs: string[] = appNavSections.flatMap((section) =>
    section.items.map((item) => item.href),
);
