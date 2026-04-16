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
        <ScrollArea className="flex flex-row w-89 max-w-89 h-200 lg:bg-lorne lg:px-4" {...props}>
            <ul className="flex flex-col justify-evenly items-stretch gap-6">                            
                {(mills && mills.length > 0) ? mills.map(mill => 
                    <li className="p-0 w-full" key={mill.match_id}>
                        <MillListItem mill={mill} />
                    </li>
                ) : (<li className="bg-beluga text-black lg:p-4 min-h-screen w-89">Loading...</li>)}
                {children}
            </ul>
        </ScrollArea>
    );
}