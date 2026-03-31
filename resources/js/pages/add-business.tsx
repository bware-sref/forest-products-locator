import {
    type County,
    type MillType,
    type State,
    type WoodSpecies,
} from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import { MillForm } from '@/components/mill-form';

export default function AddBusiness() {
    const page = usePage<{
        pageTitle?: string;
        states: State[];
        counties?: County[];
        millTypes: MillType[];
        woodSpecies: WoodSpecies[];
        // we may need to add something standard in order to catch errors
    }>();
    const pageTitle = page.props.pageTitle || 'Add Your Business';

    return (
        <AppLayout>
            <Head title={pageTitle} />
            <div className="flex min-h-screen flex-col items-center bg-nature p-6 text-beluga lg:justify-center lg:p-8 dark:bg-nature">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-83.75 flex-col-reverse lg:max-w-4xl lg:flex-row">
                        <MillForm 
                            states={page.props.states}
                            millTypes={page.props.millTypes}
                            woodSpecies={page.props.woodSpecies}
                        />
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
