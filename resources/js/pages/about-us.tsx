import AppLayout from '@/layouts/app-layout';
import { type PageSeoOverride } from '@/types';
import { usePage } from '@inertiajs/react';
import { Seo } from '@/components/seo';

export default function AboutUs() {
    const { pageSeo } = usePage<{ pageSeo: PageSeoOverride }>().props;
    const pageTitle = pageSeo.title;

    return (
        <AppLayout>
            <Seo {...pageSeo} />
            <div className="flex min-h-screen lg:min-h-72 flex-col items-center bg-nature p-6 text-white lg:justify-center lg:p-8 dark:bg-nature max-w-full lg:max-w-7xl mx-auto">
                <div className="flex flex-col w-full items-start justify-start opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 px-6">
                    <h1 className="text-3xl mb-6">{pageTitle}</h1>
                    <div className="text-xl flex flex-col space-y-6 lg:max-w-5xl">
                        <p>The ForestProductsLocator is an online forest product
                            manufacturing directory.&nbsp; A forest product directory can be used by buyers in the wood product marketplace and by forest managers wishing to sell timber products.&nbsp;&nbsp; State forestry  agencies in the South publish various forms of forest industry directories using data collected during biennial surveys of the primary forest product industry.&nbsp; The ForestProductsLocator online forest products directory is a tool, produced by <strong>Services, Utilization, and Marketing Task Force (SUM)</strong>, that combines these state directories for participating states in the South.</p>
                        <p>The intent of the The ForestProductsLocator website is to provide the user contact information on the appropriate mill(s) that will fit his or her needs. The site provides mill location, mill product type, species group utilized, and relative size of the mill; in addition to the contact information.&nbsp; This directory only includes primary forest product manufacturing companies.</p>
                        <p>A primary manufacturer uses logs (small and large) or wood chips to produce a product.&nbsp; Examples include &nbsp;a sawmill that produces lumber from logs and a pulpmill that produces liner board paper from pulpwood.&nbsp; A furniture mill that uses lumber to produce furniture and a cardboard box plant that produces boxes from linerboard are both examples of secondary manufacturing companies, which are not included on the ForestProductsLocator directory.</p>
                        <p>The SUM Task Force is a function of the <a className="external-link underline hover:no-underline" href="https://southernforests.org/" target="_blank">Southern Group of State Foresters</a> and includes forest utilization specialists from all thirteen southern states.</p>
                        <p>ForestProductsLocator.org is supported technically by the <a className="external-link underline hover:no-underline" href="https://sref.info/about/who-we-are" target="_blank">Southern Regional Extension Forestry</a> group.</p>
                    </div>                    
                </div>
            </div>
        </AppLayout>
    );
}
