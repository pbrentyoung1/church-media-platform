import './bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { InertiaProgress } from '@inertiajs/progress';

// Bootstrap 5 CSS and JS
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

const appName = import.meta.env.VITE_APP_NAME || 'ForWorship';

// Configure Inertia progress indicator
InertiaProgress.init({
    delay: 250,
    color: '#4f46e5',
    includeCSS: true,
    showSpinner: false,
});

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mixin({
                methods: {
                    // Global route helper method
                    route: window.route,
                    // Global method to access current tenant
                    tenant() {
                        return this.$page.props.shared?.tenant || null;
                    },
                    // Global method to access current user
                    auth() {
                        return this.$page.props.shared?.auth || null;
                    },
                    // Global method to check permissions
                    can(permission) {
                        return this.$page.props.permissions?.[permission] || false;
                    },
                    // Global method to format dates
                    formatDate(date, options = {}) {
                        return new Date(date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            ...options
                        });
                    },
                    // Global method to format relative time
                    formatRelative(date) {
                        const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
                        const now = new Date();
                        const target = new Date(date);
                        const diff = target.getTime() - now.getTime();
                        const days = Math.round(diff / (1000 * 60 * 60 * 24));
                        
                        if (Math.abs(days) < 1) {
                            const hours = Math.round(diff / (1000 * 60 * 60));
                            return rtf.format(hours, 'hour');
                        }
                        return rtf.format(days, 'day');
                    }
                }
            })
            .mount(el);
    },
    progress: {
        color: '#4f46e5',
    },
});
