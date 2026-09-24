import React from 'react';
import AppLayout from "@/layouts/app-layout";


interface ErrorProps {
    status: number;
}

const Error: React.FC<ErrorProps> = ({ status }) => {
    const title = {
        503: '503: Service Unavailable',
        500: '500: Server Error',
        429: '429: Rate Limit Exceeded',
        404: '404: Not Found',
        403: '403: Forbidden',
    }[status] || 'An error occurred';

    const description = {
        503: 'We\'re all out at the moment.',
        500: 'Uh oh...',
        429: 'Sorry, you have exceeded the limit for this type of request. Please try again later.',
        404: 'We\'re sorry, the resource you requested could not be found.',
        403: 'Sorry, you cannot do that.',
    }[status] || 'An expected error occurred. :-D';

    return (
        <AppLayout>
            <div className="flex max-h-screen flex-col items-center bg-nature p-6 text-beluga lg:justify-center lg:p-8 dark:bg-nature">
                <div className="flex flex-col w-full lg:max-w-7xl items-center Zlg:items-start justify-start opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 px-6 gap-5">
                    <h1 className="text-3xl font-bold">{title}</h1>
                    <p className="text-xl">{description}</p>
                </div>
            </div>
        </AppLayout>
    );
};

export default Error;