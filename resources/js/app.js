import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';

// --- PERUBAHAN DI SINI ---
// 1. Ambil library dari NPM (bukan folder vendor)
import { ZiggyVue } from 'ziggy-js';
// 2. Ambil daftar route yang sudah di-generate tadi
import { Ziggy } from './ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            // 3. Masukkan konfigurasi route ke plugin
            .use(ZiggyVue, Ziggy) 
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});