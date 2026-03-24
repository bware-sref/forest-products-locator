import AppLayout from '@/layouts/app-layout';
// import { type SharedData } from '@/types';
// import { Head, usePage } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { ComplexMap } from '@/components/complex-map';

export default function OldMillMap() {
    // const page = usePage<SharedData>();
    const pageTitle = "Mill Map";
    // temporary work-around for unused properties lint
    // eslint complains about altering page.props.
    // page.props.pageTitle = pageTitle;
    // we need to override the styles on the <main> element in the app layout for this page
    // there's almost certainly a better way to do this...
    // turns out there might not be...
    // eslint complains about the next line
    // page.props.contentClassName = 'max-w-screen';
    
    return (
        <AppLayout>
            <Head title={pageTitle} />
            <ComplexMap />
        </AppLayout>
    );
}
