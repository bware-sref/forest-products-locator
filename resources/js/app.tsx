import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    strictMode: true,
    title: (title) => (title ? `${title} - ${appName}` : appName),
    pages: {
        path: './pages',
        extension: '.tsx',
        lazy: true,
        transform: (name) => name.replace('/', '-'),
    },
    progress: {
        color: '#4B5563',
    },
    // enable view transitions globally
    defaults: {
        visitOptions: (href, options) => {
            return { viewTransition: true };
        }
    },
});
