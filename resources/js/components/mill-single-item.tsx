import {
    lazy,
    Suspense,
} from 'react';
import {
    cn
} from '@/lib/utils';
import {
    IMillListItemProps,
} from '@/types';
import {
    Link,
} from '@inertiajs/react';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ClientOnly } from '@/components/client-only';
import {
    ArrowRightIcon,
} from 'lucide-react';

// MillSingleMap pulls in react-leaflet/leaflet, which touch window/document
// at import time and crash SSR. Lazy-loading it (and gating rendering with
// ClientOnly below) keeps that module out of the server render entirely.
const MillSingleMap = lazy(() => import('@/components/mill-single-map'));


export default function MillSingleItem ({mill, children, ...props}: IMillListItemProps) {
    const cardClassName = props.className ?? '';
    const showContact = !!(mill.telephone || mill.fax || mill.email || mill.web_site);
    const showMap = mill.latitude && mill.longitude;

    return (
        <Card 
            className={cn(
                cardClassName,
                "pt-0 w-full gap-6"
            )}
            {...props}
        >
            <CardHeader
                className="border-b border-sonic px-0 pb-0 [.border-b]:pb-0 content-center items-center auto-rows-max grid-rows-1 gap-y-0"
            >
                <CardTitle
                    className="font-extrabold text-velvet text-lg pl-4 py-2 content-center items-center h-full self-stretch leading-6"
                >
                  <h1>{mill.mill_name}</h1>
                </CardTitle>
                <CardAction
                    className="bg-coupe h-full rounded-tr-lg pr-4 pt-4 pb-3 pl-2 self-stretch"
                >
                    <ArrowRightIcon 
                        data-icon="inline-end"
                        size={40}
                        className="w-8 h-8 size-8 ml-2 text-beluga"
                    />
                </CardAction>
            </CardHeader>
            <CardContent
                className="px-4 pt-0 flex flex-col gap-1 text-[16px]"
            >
                <address
                    className="not-italic pb-2"
                >
                    {mill.physical_address}
                    <br />
                    {mill.physical_address_two}
                </address>

                <p><strong>Species: </strong> 
                    {mill.wood_species ? mill.wood_species.map((wood, index) => {
                        const prefix = (0 < index) ? ', ' : '';
                        return prefix + wood.name;
                    }) : ''}
                </p>
                <p><strong>Mill Type: </strong> {
                    mill.mill_types ? mill.mill_types.map((millType, index) => {
                        const prefix = (0 < index) ? ', ' : '';                                            
                        return prefix + millType.name;
                    }) : ''
                }</p>

                {/** Contact */}
                <div className="mill-contact my-5" hidden={!showContact}>
                    <h2 className="font-bold text-lg mb-4">Contact</h2>
                    {/** Gemini says to wrap contact info <dl> in an address tag. */}
                    <address className="not-italic">
                        <dl className="grid grid-cols-3 max-w-80 gap-y-2">
                            {mill.telephone && (
                                <>
                                    <dt className="font-bold">Telephone</dt>
                                    <dd className="col-span-2">
                                        <a 
                                            className="underline hover:no-underline"
                                            href={'tel:' + mill.telephone}
                                        >
                                            {mill.telephone}
                                        </a>
                                    </dd>
                                </>
                            )}
                            {mill.fax && (
                                <>
                                    <dt className="font-bold">Fax</dt>
                                    <dd className="col-span-2">                                        
                                        {mill.fax}
                                    </dd>
                                </>
                            )}
                            {mill.email && (
                                <>
                                    <dt className="font-bold">Email</dt>
                                    <dd className="col-span-2">
                                        <a
                                            className="underline hover:no-underline"
                                            href={'mailto:' + mill.email}
                                        >
                                            {mill.email}
                                        </a>
                                        
                                    </dd>
                                </>
                            )}
                            {mill.web_site && (
                                <>
                                    <dt className="font-bold">Web Site</dt>
                                    <dd className="col-span-2">
                                        <Link
                                            viewTransition
                                            className="underline hover:no-underline"
                                            href={mill.web_site}
                                            target="_blank"
                                        >
                                            {mill.web_site}
                                        </Link>
                                    </dd>
                                </>
                            )}

                        </dl>
                    </address>
                </div>

                {/** Map */}
                {/**
                 * What should we do if the Mill doesn't have a lat & long?
                 * Well, it shouldn't be in the map, should it?
                 * Should we pass MillSingleMap as a child instead?
                 * Yeah, maybe...
                 */}
                {showMap && (
                    <ClientOnly fallback={<div className="min-h-[calc(100vh-6rem)] lg:min-h-96 w-full" />}>
                        <Suspense fallback={<div className="min-h-[calc(100vh-6rem)] lg:min-h-96 w-full" />}>
                            <MillSingleMap mills={[mill]} />
                        </Suspense>
                    </ClientOnly>
                )}

                { children }
            </CardContent>
        </Card>
    );
}