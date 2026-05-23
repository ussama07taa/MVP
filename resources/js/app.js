import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp, Link, Head } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import AdminLayout from './Layouts/AdminLayout.vue';
import ToastNotification from './Components/ToastNotification.vue';

import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);

const appElement = document.getElementById('app');
if (appElement) {
  createInertiaApp({
    resolve: (name) => {
      const page = resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'));
      page.then((module) => {
        // Default to AdminLayout for all pages except PosApp and Auth pages
        if (name !== 'PosApp' && !name.startsWith('Auth/')) {
          module.default.layout = module.default.layout || AdminLayout;
        }
      });
      return page;
    },
    setup({ el, App, props, plugin }) {
      window.authUser = props.initialPage.props.auth?.user;
      const app = createApp({ render: () => h(App, props) });
      app.use(plugin);
      app.use(pinia);
      app.component('Link', Link);
      app.component('Head', Head);
      app.component('ToastNotification', ToastNotification);
      app.mount(el);
    },
    progress: {
      color: '#4B5563',
    },
  });
}

