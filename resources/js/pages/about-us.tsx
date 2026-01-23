import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

export default function AboutUs() {
    const page = usePage<SharedData>();
    const pageTitle = "About Us";
    // temporary work-around for unused properties lint
    page.props.pageTitle = pageTitle;

    return (
        <AppLayout>
            <Head title={pageTitle} />
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col-reverse lg:max-w-4xl lg:flex-row">
                        <p>The ForestProductsLocator is an online forest product
                            manufacturing directory.&nbsp; A forest product directory can be used by buyers in the wood product marketplace and by forest managers wishing to sell timber products.&nbsp;&nbsp; State forestry  agencies in the South publish various forms of forest industry directories using data collected during biennial surveys of the primary forest product industry.&nbsp; The ForestProductsLocator online forest products directory is a tool, produced by Services, Utilization, and Marketing Task Force (SUM), that combines these state directories for participating states in the South.</p>
                        <p>The intent of the The ForestProductsLocator website is to provide the user contact information on the appropriate mill(s) that will fit his or her needs. The site provides mill location, mill product type, species group utilized, and relative size of the mill; in addition to the contact information.&nbsp; This directory only includes primary forest product manufacturing companies.</p>
                        <p>A primary manufacturer uses logs (small and large) or wood chips to produce a product.&nbsp; Examples include &nbsp;a sawmill that produces lumber from logs and a pulpmill that produces liner board paper from pulpwood.&nbsp; A furniture mill that uses lumber to produce furniture and a cardboard box plant that produces boxes from linerboard are both examples of secondary manufacturing companies, which are not included on the ForestProductsLocator directory.</p>
                        <p>The SUM Task Force is a function of the Southern Group of State Foresters and includes forest utilization specialists from all thirteen southern states.</p>
                        <p>ForestProductsLocator.org is supported technically by the <a className="external-link" href="https://sref.info/about/who-we-are" target="_blank">Southern Regional Extension Forestry</a> group.</p>
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </AppLayout>
    );
}
