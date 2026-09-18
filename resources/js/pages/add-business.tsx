import {
    type County,
    type Mill,
    type MillType,
    type PageSeoOverride,
    type State,
    type WoodSpecies,
} from '@/types';
import AppLayout from '@/layouts/app-layout';
import { usePage } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import { MillForm } from '@/components/mill-form';

export default function AddBusiness() {
    const page = usePage<{
        pageTitle?: string;
        pageSeo: PageSeoOverride;
        states: State[];
        counties?: County[];
        millTypes: MillType[];
        woodSpecies: WoodSpecies[];
        // we may need to add something standard in order to catch errors
        // or to handle edits!
        mill?: Mill;
    }>();
    const pageTitle = page.props.pageTitle || 'Add Your Business';

    return (
        <AppLayout>
            <Seo {...page.props.pageSeo} />
            <div className="flex min-h-screen flex-col items-center bg-nature p-6 text-beluga lg:justify-center lg:p-8 dark:bg-nature">
                <div className="flex flex-col w-full items-start justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 md:max-w-md">
                    <h1 className="text-3xl text-bold Xw-full Xlg:max-w-6xl mb-6">{pageTitle}</h1>
                    <MillForm 
                        states={page.props.states}
                        millTypes={page.props.millTypes}
                        woodSpecies={page.props.woodSpecies}
                        mill={page.props.mill}
                    />
                </div>
                {/** 
                 * I keep forgetting to wonder about the element below 
                 * I think it's just something to force a minimum page height on large screens.
                 * */}
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
