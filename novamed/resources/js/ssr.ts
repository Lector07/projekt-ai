import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from '@vue/server-renderer';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Component, createSSRApp, h, Plugin } from 'vue';
import { route as ziggyRoute } from 'ziggy-js';

// Definicje typów dla Inertia i Ziggy
interface Page {
    props: {
        ziggy: {
            location: string;
            [key: string]: any;
        };
        [key: string]: any;
    };
}

interface SetupProps {
    App: Component;
    props: any;
    plugin: Plugin;
}

// Deklaracja dla Node.js - tylko jedna deklaracja
declare global {
    var route: (name: string, params?: any, absolute?: boolean) => string;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page: Page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title: string) => `${title} - ${appName}`,
        resolve: (name: string) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue')),
        setup({ App, props, plugin }: SetupProps) {
            const app = createSSRApp({ render: () => h(App, props) });

            // Configure Ziggy for SSR...
            const ziggyConfig = {
                ...page.props.ziggy,
                location: new URL(page.props.ziggy.location),
            };

            // Create route function...
            const routeFunction = (name: string, params?: any, absolute?: boolean) =>
                ziggyRoute(name, params, absolute, ziggyConfig);

            // Make route function available globally...
            app.config.globalProperties.route = routeFunction;

            // Make route function available globally for SSR...
            if (typeof window === 'undefined') {
                (globalThis as any).route = routeFunction;
            }

            app.use(plugin);

            return app;
        },
    }),
);
