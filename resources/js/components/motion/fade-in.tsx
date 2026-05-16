import { motion, useReducedMotion, type HTMLMotionProps } from 'motion/react';
import type { ReactNode } from 'react';

type FadeInProps = Omit<HTMLMotionProps<'div'>, 'children'> & {
    children: ReactNode;
    delay?: number;
    y?: number;
};

export function FadeIn({
    children,
    className,
    delay = 0,
    y = 10,
    ...props
}: FadeInProps) {
    const reduceMotion = useReducedMotion();

    if (reduceMotion) {
        return <div className={className}>{children}</div>;
    }

    return (
        <motion.div
            initial={{ opacity: 0, y }}
            animate={{ opacity: 1, y: 0 }}
            transition={{
                duration: 0.4,
                delay,
                ease: [0.22, 1, 0.36, 1],
            }}
            className={className}
            {...props}
        >
            {children}
        </motion.div>
    );
}
