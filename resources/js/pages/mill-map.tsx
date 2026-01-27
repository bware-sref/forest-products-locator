import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { ComplexMap } from '@/components/complex-map';

export default function MillMap() {
    const page = usePage<SharedData>();
    const pageTitle = "Mill Map";
    // temporary work-around for unused properties lint
    page.props.pageTitle = pageTitle;
    // we need to override the styles on the <main> element in the app layout for this page
    // there's almost certainly a better way to do this...
    // turns out there might not be...
    page.props.contentClassName = 'max-w-screen';
    
    return (
        <AppLayout>
            <Head title={pageTitle} />
            <ComplexMap />
        </AppLayout>
    );
}
