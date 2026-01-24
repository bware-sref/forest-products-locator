import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { ComplexMap } from '@/components/complex-map';

export default function MillMap() {
    const page = usePage<SharedData>();
    const pageTitle = "Mill Map";
    // temporary work-around for unused properties lint
    page.props.pageTitle = pageTitle;

    return (
        <AppLayout>
            <Head title={pageTitle} />
{/*            
            <div className="flex min-h-screen flex-col items-center bg-nature text-beluga lg:justify-center dark:bg-nature">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col-reverse lg:max-w-full lg:flex-row">
 */}                    
                        <ComplexMap />
{/* 
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
*/}
        </AppLayout>
    );
}
