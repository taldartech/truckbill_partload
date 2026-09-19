import { createApp, h, Fragment } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import Vue3Toastify from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'
import AppResponseLoader from '@/Components/Loading/AppResponseLoader.vue';

const syncCsrfMeta = (token) => {
    if (!token) return;
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) meta.setAttribute('content', token);
};

router.on('success', (event) => {
    syncCsrfMeta(event.detail.page.props.csrf_token);
});

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({
            render: () => h(Fragment, [
                h(App, props),
                h(AppResponseLoader, { trackInertia: true }),
            ]),
        })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#2FB36F',
    },
});
