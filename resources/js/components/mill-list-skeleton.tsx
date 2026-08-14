import {
    cn
} from '@/lib/utils';
import {
    IMillListSkeletonProps,
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
    Skeleton,
} from '@/components/ui/skeleton';
import {
    ArrowRightIcon,
} from 'lucide-react';


export default function MillListSkeleton ({children, ...props}: IMillListSkeletonProps) {
    const cardClassName = props.className ?? '';

    return (
        <Card 
            className={cn(
                cardClassName,
                "pt-0 w-full gap-6 min-h-62.75"
            )}
            {...props}
        >
            {/** wrap entire header in Link to make entire header a link...whodathunk? */}
            <Link
                viewTransition
                href="#"
                className="hover:underline"
            >
                <CardHeader
                    className="border-b border-sonic px-0 pb-0 [.border-b]:pb-0 content-center items-center auto-rows-max grid-rows-1 gap-y-0"
                >
                    <CardTitle
                        className="font-extrabold text-velvet text-lg pl-4 py-2 content-center items-center h-full self-stretch leading-6"
                    >
                        <Skeleton className="h-6 w-full" />
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
            </Link>
            <CardContent
                className="px-4 pt-0 flex flex-col gap-1 text-[16px]"
            >
                <address
                    className="not-italic pb-2"
                >
                    <Skeleton className="h-4 w-full" />
                    <br />
                    <Skeleton className="h-4 w-full" />
                </address>
                {/* change from <p> to <div> because <p> cannot contain <div> */}
                <div>
                    <Skeleton className="h-5 w-full" />
                </div>
                <div>
                    <Skeleton className="h-5 w-full" />
                </div>
                <div>
                    <Skeleton className="h-5 w-full" />
                </div>
                { children }
            </CardContent>
        </Card>
    );
}