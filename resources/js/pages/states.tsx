import AppLayout from '@/layouts/app-layout';
import {
    type PageSeoOverride,
    State,
} from '@/types';
import {
    Link,
    usePage,
} from '@inertiajs/react';
import { Seo } from '@/components/seo';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { CircleArrowRight } from 'lucide-react';

export default function States() {
    const page = usePage<{
        states: State[];
        pageTitle?: string;
        pageSeo: PageSeoOverride;
    }>();

    const states = page.props.states;

    return (
        <AppLayout>
            <Seo {...page.props.pageSeo} />

            <div className="mx-auto flex w-full max-w-full flex-col md:w-6xl lg:w-7xl">
                <div className="flex max-w-83.75 flex-col px-6 lg:max-w-7xl">
                    <h1 className="mt-8 mb-6 w-full text-3xl leading-10 font-bold text-beluga lg:text-5xl">
                        {page.props.pageTitle}
                    </h1>
                </div>
            </div>

            <div className="cards mx-auto mt-4 flex w-full max-w-full flex-col flex-wrap items-stretch justify-evenly gap-8 px-5 py-6 md:w-6xl md:flex-row lg:w-7xl lg:gap-6">
                {states.map(state => (
                    <Card
                        key={state.id}
                        className="w-full border-0 rounded-2xl bg-coupe pt-0 md:w-55 lg:w-70 lg:max-w-70 xl:w-87.5 xl:max-w-95"
                    >
                        <CardHeader className="rounded-t-2xl bg-coupe py-4 xl:pt-6 xl:pb-3">
                            <CardTitle className="text-beluga">
                                <Link
                                    href={'/states/' + state.slug}
                                    className="flex justify-between text-[27px] text-beluga"
                                >
                                    <span className="inline underline hover:no-underline">{state.name}</span>
                                    <CircleArrowRight
                                        data-icon="inline-end"
                                        size={40}
                                        className="size-10"
                                    />
                                </Link>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="mr-5 bg-coupe text-xl text-beluga">
                            {state.resource_summary}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AppLayout>
    );
}
