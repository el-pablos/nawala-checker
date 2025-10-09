import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp as createInertiaReactApp } from '@inertiajs/react';

// Determine if the page is a React component (TSX) or Vue component
const isReactComponent = (name) => {
    return name.includes('nawala-checker') && !name.endsWith('.vue');
};

// Initialize Inertia app with support for both Vue and React
createInertiaApp({
    resolve: async (name) => {
        // Check if it's a React component
        if (isReactComponent(name)) {
            // Use React for TSX files
            return resolvePageComponent(
                `./pages/${name}.tsx`,
                import.meta.glob('./pages/**/*.tsx')
            );
        } else {
            // Use Vue for .vue files
            return resolvePageComponent(
                `./pages/${name}.vue`,
                import.meta.glob('./pages/**/*.vue')
            );
        }
    },
    setup({ el, App, props, plugin }) {
        const componentName = props.initialPage.component;

        if (isReactComponent(componentName)) {
            // Render React component
            const root = createRoot(el);
            root.render(React.createElement(App, props));
        } else {
            // Render Vue component
            createApp({ render: () => h(App, props) })
                .use(plugin)
                .mount(el);
        }
    },
    progress: {
        color: '#4B5563',
    },
});
