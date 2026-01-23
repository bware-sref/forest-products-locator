import React from 'react';

interface ErrorProps {
    status: number;
}

const Error: React.FC<ErrorProps> = ({ status }) => {
    const title = {
        503: '503: Service Unavailable',
        500: '500: Server Error',
        404: '404: Page Not Found',
        403: '403: Forbidden',
    }[status] || 'An error occurred';

    const description = {
        503: 'We\'re all out at the moment.',
        500: 'Uh oh...',
        404: 'Sorry, I cannot find that.',
        403: 'Sorry, you cannot do that.',
    }[status] || 'An expected error occurred. :-D';

    return (
        <div>
            <h1>{title}</h1>
            <p>{description}</p>
            <p>:shrug: what you gonna do? :-p</p>
        </div>
    );
};

export default Error;