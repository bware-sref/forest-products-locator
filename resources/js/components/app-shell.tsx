import { SidebarProvider } from '@/components/ui/sidebar';
import { SizeOMeter } from '@/components/size-o-meter';
import { ClientOnly } from '@/components/client-only';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

interface AppShellProps {
    children: React.ReactNode;
    variant?: 'header' | 'sidebar';
    // env?: 'local' | 'development' | 'testing' | 'production' | null;
}

export function AppShell({ children, variant = 'header'}: AppShellProps) {
    const props = usePage<SharedData>().props;
    // const isOpen = usePage<SharedData>().props.sidebarOpen;
    const isOpen = props.sidebarOpen;
    const env = props.env;

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col bg-nature">
                {children}
                { env !== 'local' ? '' : (
                    <ClientOnly>
                        <SizeOMeter />
                    </ClientOnly>
                )}

            </div>
        );
    }

    return <SidebarProvider defaultOpen={isOpen}>{children}</SidebarProvider>;
}
