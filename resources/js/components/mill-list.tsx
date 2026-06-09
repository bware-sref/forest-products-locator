import { 
    type MillListProps,
} from '@/types';
import {
    ScrollArea,
} from '@/components/ui/scroll-area';
import MillListItem from '@/components/mill-list-item';


/**
 * Maybe we want to allow children to inform the no mills state?
 * Or just use the shadcn empty component?
 */
export default function MillList({mills, children, ...props}: MillListProps) {

    return (
        <>
        {/**
         * Added empty component to experiment with other elements
         */}
        <ScrollArea 
            className="flex flex-row w-90 max-w-90 md:w-full md:max-w-full mt-6 lg:mt-0 h-200 lg:bg-lorne lg:px-4 lg:py-4 lg:h-screen lg:max-h-full lg:flex-wrap lg:w-full lg:max-w-full Xmd:bg-orange-500"
            {...props}
        >
            <ul className="flex flex-col md:flex-row md:flex-wrap justify-evenly items-stretch gap-6 Xbg-purple-400">
                {(mills && mills.length > 0) ? mills.map(mill => 
                    <li className="p-0 w-full md:w-75 lg:w-90" key={mill.match_id}>
                        <MillListItem mill={mill} />
                    </li>
                ) : (<li className="bg-beluga text-black lg:p-4 min-h-screen w-89 lg:w-90">Loading...</li>)}
                {children}
            </ul>
        </ScrollArea>
        </>
    );
}