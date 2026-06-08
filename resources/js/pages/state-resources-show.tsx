import AppLayout from "@/layouts/app-layout";
import {
    State,
    StateResource,
} from '@/types';
import {
    Head,
    usePage,
} from '@inertiajs/react';

export default function StateResourcesShow() {
    const page = usePage<{
        pageTitle?: string;
        state: State;
        resource: StateResource;
    }>();

    return (
        <AppLayout>
            <Head title={page.props.pageTitle} />

            {/**
             * Probably should have made a content column component a while back...
             */}
            <div className="mx-auto flex flex-col max-w-full md:w-6xl lg:w-7xl xl:max-w-7xl">
                <div className="flex flex-col max-w-83.75 lg:max-w-3xl px-6 text-beluga">
                    <h1 className="text-3xl lg:text-5xl leading-10 font-bold mt-8 mb-6 w-full text-beluga">{page.props.resource.title}</h1>
                    <div className="state-resource-content text-xl">
                        {page.props.resource.content}
                    </div>
                </div>
            </div>

        </AppLayout>
    );
}