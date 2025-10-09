import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp as createVueInertiaApp } from '@inertiajs/vue3';
import { createInertiaApp as createReactInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import React from 'react';

// Get the page component name from the data-page attribute
const el = document.getElementById('app');
const pageData = JSON.parse(el.dataset.page);
const componentName = pageData.component;

// Determine if this is a React component (TSX) based on the component path
const isReactComponent = componentName.startsWith('tools/nawala-checker/');

if (isReactComponent) {
    // Initialize React app for Nawala Checker pages
    createReactInertiaApp({
        resolve: (name) => {
            const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });
            const page = pages[`./pages/${name}.tsx`];
            if (!page) {
                throw new Error(`React page not found: ./pages/${name}.tsx`);
            }
            return page;
        },
        setup({ el, App, props }) {
            createRoot(el).render(React.createElement(App, props));
        },
        progress: {
            color: '#4B5563',
        },
    });
} else {
    // Initialize Vue app for Admin and Auth pages
    createVueInertiaApp({
        resolve: (name) => {
            const pages = import.meta.glob('./pages/**/*.vue', { eager: true });
            const page = pages[`./pages/${name}.vue`];
            if (!page) {
                throw new Error(`Vue page not found: ./pages/${name}.vue`);
            }
            return page;
        },
        setup({ el, App, props, plugin }) {
            createApp({ render: () => h(App, props) })
                .use(plugin)
                .mount(el);
        },
        progress: {
            color: '#4B5563',
        },
    });
}
