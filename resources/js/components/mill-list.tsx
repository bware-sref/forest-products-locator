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
        // <div className="flex flex-row w-85 max-w-89" {...props}>
            <ScrollArea className="flex flex-row w-85 max-w-89 h-200">
                <ul className="flex flex-col justify-evenly items-stretch gap-6">                            
                    {(mills && mills.length > 0) ? mills.map(mill => 
                        <li className="p-0 w-full" key={mill.match_id}>
                            <MillListItem mill={mill} />
                        </li>
                    ) : (<li className="bg-beluga text-black p-8 min-h-screen w-83.75">Loading...</li>)}
                    {children}
                </ul>
            </ScrollArea>
        // </div>
    );
}