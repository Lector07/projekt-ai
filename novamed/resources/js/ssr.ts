import { createSSRApp, h, Plugin, Component } from 'vue';
import { renderToString } from '@vue/server-renderer';
import { route as ziggyRoute } from 'ziggy-js';

interface Page {
    props: {
        ziggy: {
            location: string;
            [key: string]: any;
        };
        [key: string]: any;
    };
    component: Component;
}

declare global {
    let route: (name: string, params?: any, absolute?: boolean) => string;
}

export async function renderVueSSR(page: Page, plugin?: Plugin) {
    const app = createSSRApp({
        render: () => h(page.component, page.props),
    });

    const ziggyConfig = {
        ...page.props.ziggy,
        location: new URL(page.props.ziggy.location),
    };

    const routeFunction = (name: string, params?: any, absolute?: boolean) =>
        ziggyRoute(name, params, absolute, ziggyConfig);

    app.config.globalProperties.route = routeFunction;

    if (typeof window === 'undefined') {
        (globalThis as any).route = routeFunction;
    }

    if (plugin) {
        app.use(plugin);
    }

    return await renderToString(app);
}
