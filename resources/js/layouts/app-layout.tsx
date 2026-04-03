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

export default ({ children, breadcrumbs, flash, ...props }: AppLayoutProps) => (
    <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
        {children}
        <Toaster />
        
        <>
        {flash && console.log('flash!', flash)}
        </>
    </AppLayoutTemplate>
);
