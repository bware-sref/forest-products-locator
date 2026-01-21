//import { dashboard, login, register, millMap, millList, aboutUs } from '@/routes';
import { type SharedData } from '@/types';
// import { Head, Link, usePage } from '@inertiajs/react';
import { Head, usePage } from '@inertiajs/react';
import { BasicMap } from '@/components/basic-map';
import { TopNav } from '@/components/top-nav';

export default function MillMap({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const page = usePage<SharedData>();
    // const { auth } = usePage<SharedData>().props;
    const pageTitle = "Mill Map";
    // temporary work-around for unused properties lint
    page.props.pageTitle = pageTitle;

    return (
        <>
            <Head title={pageTitle}>
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <TopNav canRegister={canRegister} pageTitle={pageTitle}></TopNav>
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col-reverse lg:max-w-4xl lg:flex-row">
                        {/* replace the awesome laravel banner with a basic map */}
                        <BasicMap />
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </>
    );
}
