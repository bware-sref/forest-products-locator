import AppLayoutTemplate from '@/layouts/app/app-header-layout';
// import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { type PageFlashData } from '@inertiajs/core';
import { type ReactNode } from 'react';
import { toast, Toaster } from 'sonner';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    flash?: PageFlashData;
}

/**
 * Per InertiaJS docs, it seems like it would be good centralize the toasting flash data
 */
function handleFlashData(flash: PageFlashData) {
    
    if (flash.success || flash.type === 'success') {
        toast.success(String(flash.message || 'Success!'));
    }

    if (flash.error || flash.type === 'error') {
        toast.error(String(flash.message || 'An error occurred.'));
    }

    // we can add more types of flash messages here as needed
}

// fast refresh hates anonymous components
// export default ({ children, breadcrumbs, flash, ...props }: AppLayoutProps) => (
export default function AppLayout ({ children, breadcrumbs, flash, ...props }: AppLayoutProps) { // => (
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
            {children}
            <Toaster />        
            <>
                {/** wrap flash data handling in a fragment */}
                {flash && handleFlashData(flash)}
            </>
        </AppLayoutTemplate>
    );
}
// );
