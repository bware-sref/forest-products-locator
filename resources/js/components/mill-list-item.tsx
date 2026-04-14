import {
    cn
} from '@/lib/utils';
import {
    Mill,
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
import {
    ArrowRightIcon,
} from 'lucide-react';
import { 
    show,
} from '@/actions/App/Http/Controllers/MillController';


export default function MillListItem ({mill, children, ...props}: IMillListItemProps) {
    const cardClassName = props.className ?? '';
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
                    <Link 
                        href={show(mill.match_id)}
                        className="hover:underline self-center content-center h-full w-full"
                    >
                        {mill.mill_name}
                    </Link>
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
                <p>
                    <Link 
                        href={`https://maps.google.com/?q=${mill.latitude},${mill.longitude}`}
                        target="_blank"
                        className="underline hover:no-underline"
                    >
                        Map This Location
                    </Link>
                </p>
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
            </CardContent>
        </Card>
    );
}