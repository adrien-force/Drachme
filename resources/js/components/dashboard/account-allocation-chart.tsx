import { useEffect, useMemo, useState, type CSSProperties } from 'react';
import { useMotionValueEvent, useSpring } from 'motion/react';
import {
    Cell,
    Pie,
    PieChart,
    ResponsiveContainer,
    Sector,
    Tooltip,
} from 'recharts';
import type { PieSectorDataItem } from 'recharts/types/polar/Pie';

import {
    DASHBOARD_CHART_PLOT_CLASS,
    DashboardChartCard,
} from '@/components/dashboard/dashboard-chart-card';
import { useTranslation } from '@/hooks/use-translation';
import { resolveChartSliceColors } from '@/lib/chart-slice-colors';
import { formatCurrency } from '@/lib/format-currency';
import type { AccountAllocationSlice } from '@/types/dashboard.types';

const INNER_RADIUS = 58;
const OUTER_RADIUS = 84;
const CORNER_RADIUS = 10;
const PADDING_ANGLE = 4;
const HOVER_RADIUS_OFFSET = 4;
const SLICE_SPRING = { stiffness: 380, damping: 32 };

type AccountAllocationChartProps = {
    data: AccountAllocationSlice[];
};

function sliceGlowStyle(color: string): CSSProperties {
    return {
        backgroundColor: color,
        boxShadow: `0 0 4px color-mix(in srgb, ${color} 22%, transparent)`,
    };
}

function AllocationTooltip({
    active,
    payload,
}: {
    active?: boolean;
    payload?: Array<{ payload: AccountAllocationSlice }>;
}) {
    if (!active || !payload?.[0]) {
        return null;
    }

    const slice = payload[0].payload;

    return (
        <div className="bg-popover/95 text-popover-foreground rounded-lg border border-white/10 px-3 py-2 text-sm shadow-lg backdrop-blur-sm">
            <p className="font-medium">{slice.label}</p>
            <p className="text-muted-foreground tabular-nums">
                {formatCurrency(slice.value, { precise: true })}
            </p>
        </div>
    );
}

type AnimatedActiveSliceProps = PieSectorDataItem & {
    color: string;
    gapStroke: string;
};

function AnimatedActiveSlice({
    color,
    gapStroke,
    outerRadius = OUTER_RADIUS,
    ...sectorProps
}: AnimatedActiveSliceProps) {
    const baseOuter = Number(outerRadius);
    const target = baseOuter + HOVER_RADIUS_OFFSET;
    const spring = useSpring(baseOuter, SLICE_SPRING);
    const [animatedOuter, setAnimatedOuter] = useState(baseOuter);

    useEffect(() => {
        spring.set(target);
    }, [spring, target]);

    useMotionValueEvent(spring, 'change', (latest) => {
        setAnimatedOuter(latest);
    });

    return (
        <Sector
            {...sectorProps}
            outerRadius={animatedOuter}
            fill={color}
            cornerRadius={CORNER_RADIUS}
            stroke={gapStroke}
            strokeWidth={2}
        />
    );
}

export function AccountAllocationChart({ data }: AccountAllocationChartProps) {
    const { t } = useTranslation();
    const [activeIndex, setActiveIndex] = useState<number | undefined>(undefined);
    const [sliceColors, setSliceColors] = useState<string[]>(() =>
        resolveChartSliceColors(),
    );

    useEffect(() => {
        setSliceColors(resolveChartSliceColors());
    }, []);

    const gapStroke = useMemo(() => {
        if (typeof window === 'undefined') {
            return '#0a0a0a';
        }

        return (
            getComputedStyle(document.documentElement)
                .getPropertyValue('--card')
                .trim() || '#0a0a0a'
        );
    }, []);

    if (data.length === 0) {
        return null;
    }

    const renderActiveSlice = (sectorProps: PieSectorDataItem) => {
        const sectorIndex = data.findIndex(
            (slice) => slice.type === sectorProps.payload?.type,
        );
        const index = sectorIndex >= 0 ? sectorIndex : 0;

        return (
            <AnimatedActiveSlice
                {...sectorProps}
                color={sliceColors[index % sliceColors.length] ?? '#4ade80'}
                gapStroke={gapStroke}
            />
        );
    };

    return (
        <DashboardChartCard
            title={t('dashboard.allocation_title')}
            description={t('dashboard.allocation_description')}
            className="animate-in fade-in duration-500 fill-mode-both"
        >
            <div
                className={`${DASHBOARD_CHART_PLOT_CLASS} flex flex-col gap-6 sm:flex-row sm:items-stretch`}
            >
                <div className="relative mx-auto w-full max-w-[240px] sm:mx-0 sm:h-full sm:min-h-0 sm:flex-1">
                        <div
                            aria-hidden
                            className="pointer-events-none absolute inset-0 flex items-center justify-center"
                        >
                            <div className="size-28 rounded-full bg-primary/5 blur-2xl" />
                        </div>
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <defs>
                                    <filter
                                        id="allocation-glow"
                                        x="-12%"
                                        y="-12%"
                                        width="124%"
                                        height="124%"
                                    >
                                        <feGaussianBlur
                                            in="SourceGraphic"
                                            stdDeviation="1.5"
                                            result="blur"
                                        />
                                        <feColorMatrix
                                            in="blur"
                                            type="matrix"
                                            values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 0.2 0"
                                            result="softBlur"
                                        />
                                        <feMerge>
                                            <feMergeNode in="softBlur" />
                                        </feMerge>
                                    </filter>
                                </defs>
                                <Pie
                                    data={data}
                                    dataKey="value"
                                    nameKey="label"
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={INNER_RADIUS}
                                    outerRadius={OUTER_RADIUS + 3}
                                    paddingAngle={PADDING_ANGLE}
                                    cornerRadius={CORNER_RADIUS}
                                    stroke="none"
                                    isAnimationActive
                                    animationDuration={900}
                                    animationEasing="ease-out"
                                >
                                    {data.map((slice, index) => (
                                        <Cell
                                            key={`glow-${slice.type}`}
                                            fill={
                                                sliceColors[index % sliceColors.length]
                                            }
                                            fillOpacity={0.22}
                                            filter="url(#allocation-glow)"
                                        />
                                    ))}
                                </Pie>
                                <Pie
                                    data={data}
                                    dataKey="value"
                                    nameKey="label"
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={INNER_RADIUS}
                                    outerRadius={OUTER_RADIUS}
                                    paddingAngle={PADDING_ANGLE}
                                    cornerRadius={CORNER_RADIUS}
                                    activeIndex={activeIndex}
                                    activeShape={renderActiveSlice}
                                    onMouseEnter={(_, index) => setActiveIndex(index)}
                                    onMouseLeave={() => setActiveIndex(undefined)}
                                    isAnimationActive
                                    animationBegin={0}
                                    animationDuration={700}
                                    animationEasing="ease-out"
                                >
                                    {data.map((slice, index) => (
                                        <Cell
                                            key={slice.type}
                                            fill={
                                                sliceColors[index % sliceColors.length]
                                            }
                                            stroke={gapStroke}
                                            strokeWidth={2}
                                        />
                                    ))}
                                </Pie>
                                <Tooltip content={<AllocationTooltip />} />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                <ul className="flex flex-1 flex-col justify-center gap-3 text-sm sm:min-h-0">
                        {data.map((slice, index) => (
                            <li
                                key={slice.type}
                                className="flex items-center justify-between gap-3 rounded-lg px-1 py-0.5 transition-colors hover:bg-muted/30"
                                onMouseEnter={() => setActiveIndex(index)}
                                onMouseLeave={() => setActiveIndex(undefined)}
                            >
                                <span className="flex min-w-0 items-center gap-2.5">
                                    <span
                                        className="size-2.5 shrink-0 rounded-full ring-1 ring-white/15"
                                        style={sliceGlowStyle(
                                            sliceColors[index % sliceColors.length] ??
                                                '#4ade80',
                                        )}
                                    />
                                    <span className="truncate">{slice.label}</span>
                                </span>
                                <span className="text-muted-foreground shrink-0 tabular-nums">
                                    {formatCurrency(slice.value, { precise: true })}
                                </span>
                            </li>
                        ))}
                </ul>
            </div>
        </DashboardChartCard>
    );
}
