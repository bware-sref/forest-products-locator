import AppLayout from "@/layouts/app-layout";
import DOMPurify from 'isomorphic-dompurify';
import {
    type PageSeoOverride,
    State,
    StateResource,
} from '@/types';
import {
    Link,
    usePage,
} from '@inertiajs/react';
import { Seo } from '@/components/seo';

export default function StateResourcesShow() {
    const page = usePage<{
        pageTitle?: string;
        pageSeo: PageSeoOverride;
        state: State;
        resource: StateResource;
    }>();

    const content = DOMPurify.sanitize(page.props.resource.content);


    return (
        <AppLayout>
            <Seo {...page.props.pageSeo} />

            {/**
             * Probably should have made a content column component a while back...
             */}
            <div className="mx-auto flex flex-col max-w-full md:w-6xl lg:w-7xl xl:max-w-7xl">
                <div className="flex flex-col lg:flex-row max-w-83.75 lg:max-w-7xl px-6 text-beluga items-center">
                    <h1 className="text-3xl lg:text-5xl leading-10 font-bold mt-8 mb-6 w-full text-beluga">{page.props.resource.title}</h1>
                    {/**
                     * @TODO: add link back to this state's resources
                     */}
                    <Link className="underline hover:no-underline" 
                        href={'/state-resources/' + page.props.state.slug}>
                        Back to {page.props.state.name} Resources
                    </Link>
                </div>
                <div className="flex flex-col max-w-83.75 lg:max-w-3xl px-6 text-beluga">
                    {/**
                     * Outputting raw HTML is weird in React...
                     */}
                    <div 
                        className="state-resource-content text-xl"
                        dangerouslySetInnerHTML={{ __html: content }}
                    >
                        {/**
                         * We may need to output raw content since CKEditor may include HTML...
                         */}
                        {/* {page.props.resource.content} */}
                    </div>
                </div>
            </div>

        </AppLayout>
    );
}