import AppLayout from "@/layouts/app-layout";
import {
    State,
    StateResource,
} from '@/types';
import {
    Head,
    Link,
    usePage,
} from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    CircleArrowRight,
} from 'lucide-react';

export default function StateResourcesList() {
    const page = usePage<{
        state: State;
        pageTitle?: string;
    }>();
    
    const state = page.props.state;
    const resources: StateResource[] = page.props.state.state_resources || [];

    console.log('state: ', state);
    console.log('resources: ', resources);

    return (
        <AppLayout>
            <Head title={page.props.pageTitle} />
            {/** 
             * Do we need another Hero?
             * Maybe not, but we need something constrain width.
             */}
            <div className="mx-auto flex flex-col max-w-full md:w-6xl lg:w-7xl text-beluga">
                <div className="flex flex-col max-w-83.75 lg:max-w-7xl px-6">
                    <h1 className="text-3xl lg:text-5xl leading-10 font-bold mt-8 mb-6 w-full text-beluga">{page.props.pageTitle}</h1>
                    <Link className="underline hover:no-underline"
                        href="/state-resources"
                        >Back to All State Resources</Link>
                </div>
            </div>

            {/**
             * Cards!
             * Makes me realize we might want to add a 'summary' column to the state_resources table.
             */}
             <div className="cards mx-auto mt-4 py-6 px-5 flex flex-col flex-wrap md:flex-row w-full md:w-6xl lg:w-7xl max-w-full  items-stretch justify-evenly gap-8 lg:gap-6 Xbg-pink-500">
                {resources.map(resource => 
                    <Card key={resource.id} className="w-full md:w-55 lg:w-70 lg:max-w-70 xl:w-87.5 xl:max-w-95 pt-0 border-0 rounded-2xl bg-coupe">
                        <CardHeader className="bg-coupe py-4 xl:pt-6 xl:pb-3 rounded-t-2xl">
                            <CardTitle className="text-beluga">
                                <Link
                                    href={'/state-resources/' + state.slug + '/' + resource.id}
                                    className="text-beluga flex justify-between text-[27px]"
                                >
                                    <span className="underline inline hover:no-underline">
                                        {resource.title}
                                    </span>
                                    <CircleArrowRight 
                                        data-icon="inline-end"
                                        size={40}
                                        className="size-10"
                                    />
                                </Link>                                
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="mr-5 bg-coupe text-beluga text-xl">
                            {/**
                             * This actually needs to be an exerpt or summary of the content.
                             */}
                            {resource.content}
                        </CardContent>
                    </Card>
                )}
             </div>
        </AppLayout>
    );
}