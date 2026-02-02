import { SidebarInset } from '@/components/ui/sidebar';
import * as React from 'react';
import { cn } from '@/lib/utils';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

interface AppContentProps extends React.ComponentProps<'main'> {
    variant?: 'header' | 'sidebar';
    contentClassName?: string;
}

export function AppContent({
    variant = 'header',
    children,
    ...props
}: AppContentProps) {
    const page = usePage<SharedData>();
    // contentClassName relies on modifying page.props in each page file which is apparently a no-no
    // leaving the prop contentClassName for now to avoid needing to remove a lot of things as unused
    const contentClassName = page.props.contentClassName || 'burst';
    if (variant === 'sidebar') {
        return <SidebarInset {...props}>{children}</SidebarInset>;
    }
    return (
        <main
            className={cn("mx-auto flex h-full w-full max-w-screen flex-1 flex-col", contentClassName)}
            {...props}
        >
            {children}
        </main>
    );
}
