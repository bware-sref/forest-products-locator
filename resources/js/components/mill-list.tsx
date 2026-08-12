import { 
    type MillListProps,
} from '@/types';
import {
    ScrollArea,
} from '@/components/ui/scroll-area';
import MillListSkeleton from '@/components/mill-list-skeleton';
import MillListItem from '@/components/mill-list-item';


/**
 * Maybe we want to allow children to inform the no mills state?
 * Or just use the shadcn empty component?
 */
export default function MillList({mills, children, ...props}: MillListProps) {

    // 12 skeletons ensures scrollbar immediate present on large screens
    const howManySkeletons = 12;
    
    return (
        <>
        {/**
         * Added empty component to experiment with other elements
         */}
        <ScrollArea 
            className="flex flex-row w-90 max-w-90 md:w-full md:max-w-full mt-6 lg:mt-0 h-200 lg:bg-lorne lg:px-4 lg:py-4 lg:h-screen lg:max-h-full lg:flex-wrap lg:w-full lg:max-w-full"
            {...props}
        >
            <ul className="flex flex-col md:flex-row md:flex-wrap justify-evenly items-stretch gap-6">
                {(mills && mills.length > 0) ? mills.map(mill => 
                    <li className="p-0 w-full md:w-75 lg:w-90" key={mill.match_id}>
                        <MillListItem mill={mill} />
                    </li>
                ) : (
                    // have to wrap multiples in a fake parent tag
                    // also, you apparently can't put a for loop inside of a JSX component
                    // instead, we make an array and then map over it
                    <>
                    {Array.from({ length: howManySkeletons}).map((_, index) => (
                        <li className="p-0 w-full md:w-75 lg:w-90" key={index}>
                            <MillListSkeleton />
                        </li>
                    ))}
                    </>
                )}
                {children}
            </ul>
        </ScrollArea>
        </>
    );
}