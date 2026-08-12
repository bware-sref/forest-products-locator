import AppLayout from '@/layouts/app-layout';
import { type PageSeoOverride } from '@/types';
import { usePage } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import ComingSoon from '@/components/coming-soon';

export default function Accessibility() {
    const { pageSeo } = usePage<{ pageSeo: PageSeoOverride }>().props;

    return (
        <AppLayout>
            <Seo {...pageSeo} />
            <ComingSoon pageTitle={pageSeo.title} />
        </AppLayout>
    );
}
