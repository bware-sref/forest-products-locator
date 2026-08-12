import {
    cn,
} from '@/lib/utils';
import CardSkeleton from '@/components/card-skeleton';
import {
    ReactNode
} from 'react';

interface IComingSoonProps {
    pageTitle?: string;
    caption?: string;
    children?: ReactNode;
    [key: string]: unknown;
}

const defaultWrapperClassName = 'flex min-h-[30vh] md:min-h-[70vh] flex-col items-start bg-nature p-6 text-beluga lg:justify-center lg:p-8 dark:bg-nature w-full md:max-w-6xl lg:max-w-7xl mx-auto';

export default function ComingSoon({
    pageTitle = 'Coming Soon',
    caption = 'Coming Soon...',
    children,
    ...props 
}: IComingSoonProps) {

    const wrapperClassName = props.className ?? '';

    return (
        <div 
            className={cn(
                defaultWrapperClassName,
                wrapperClassName
            )}
            {...props}
        >
            <h1 className="text-beluga text-4xl">{pageTitle}</h1>
            <div className="flex flex-col w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                <div className="flex flex-col items-center justify-start bg-velvet w-2/3 py-10">
                    <h2 className="text-2xl text-beluga mb-10 mx-10 self-start">{caption}</h2>
                    <ul>
                        <li className="p-0 w-full md:w-75 lg:w-90">
                            <CardSkeleton />
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    );
}