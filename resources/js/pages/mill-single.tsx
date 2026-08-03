import AppLayout from '@/layouts/app-layout';
import {
    type Mill,
    type PageSeoOverride,
} from '@/types';
import {
    usePage
} from '@inertiajs/react';
import { Seo } from '@/components/seo';
import MillSingleItem from '@/components/mill-single-item';


export default function MillSingle() {
    const page = usePage<{
        mills: Mill[];
        pageSeo: PageSeoOverride;
    }>();
    const mills = page.props.mills || [];
    const mill = mills[0];


    return (
        <AppLayout>
            <Seo {...page.props.pageSeo} />
            <div className="flex min-h-screen flex-col items-center p-6 text-velvet lg:justify-center lg:p-8">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <div className="flex flex-col justify-evenly items-stretch gap-1 w-full max-w-89 lg:max-w-xl">
                        <MillSingleItem mill={mill} />
                    </div>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
