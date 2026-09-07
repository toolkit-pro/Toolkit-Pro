/**
 * Toolkit Pro v2.0
 * Main Application JavaScript
 */

// ===================
// Imports
// ===================
import './bootstrap';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import { createI18n } from 'vue-i18n';
import { createHead } from '@vueuse/head';
import axios from 'axios';
import NProgress from 'nprogress';
import 'nprogress/nprogress.css';

// Components
import App from './App.vue';
import ToolCard from './components/ToolCard.vue';
import SearchBar from './components/SearchBar.vue';
import Dashboard from './components/Dashboard.vue';

// Stores
import { useAuthStore } from './stores/auth';
import { useToolStore } from './stores/tools';
import { useNotificationStore } from './stores/notifications';
import { useSettingsStore } from './stores/settings';

// Router
import routes from './router';

// Locales
import messages from './locales';

// ===================
// Configuration
// ===================

// Axios Configuration
axios.defaults.baseURL = window.Laravel?.apiUrl || '/api/v1';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['X-CSRF-TOKEN'] = window.Laravel?.csrfToken;
axios.defaults.withCredentials = true;

// Axios Interceptors
axios.interceptors.request.use(
    (config) => {
        // NProgress শুরু
        NProgress.start();
        
        // Auth টোকেন যোগ
        const token = localStorage.getItem('access_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        
        // Request ID যোগ
        config.headers['X-Request-ID'] = generateRequestId();
        
        return config;
    },
    (error) => {
        NProgress.done();
        return Promise.reject(error);
    }
);

axios.interceptors.response.use(
    (response) => {
        NProgress.done();
        return response;
    },
    async (error) => {
        NProgress.done();
        
        const originalRequest = error.config;
        
        // 401 Unauthorized
        if (error.response?.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;
            
            try {
                const refreshToken = localStorage.getItem('refresh_token');
                if (refreshToken) {
                    const response = await axios.post('/auth/refresh-token', {
                        refresh_token: refreshToken,
                    });
                    
                    const { token } = response.data.data;
                    localStorage.setItem('access_token', token);
                    
                    originalRequest.headers.Authorization = `Bearer ${token}`;
                    return axios(originalRequest);
                }
            } catch (refreshError) {
                // রিফ্রেশ ব্যর্থ হলে লগআউট
                localStorage.removeItem('access_token');
                localStorage.removeItem('refresh_token');
                window.location.href = '/login';
            }
        }
        
        // 403 Forbidden
        if (error.response?.status === 403) {
            console.warn('Access forbidden:', error.response.data);
        }
        
        // 429 Rate Limited
        if (error.response?.status === 429) {
            const retryAfter = error.response.headers['retry-after'];
            console.warn(`Rate limited. Retry after ${retryAfter} seconds`);
        }
        
        return Promise.reject(error);
    }
);

// ===================
// NProgress Configuration
// ===================
NProgress.configure({
    showSpinner: false,
    minimum: 0.1,
    speed: 200,
    trickleSpeed: 100,
});

// ===================
// Router Configuration
// ===================
const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        }
        if (to.hash) {
            return { el: to.hash, behavior: 'smooth' };
        }
        return { top: 0, behavior: 'smooth' };
    },
});

// Router Guards
router.beforeEach(async (to, from, next) => {
    NProgress.start();
    
    const authStore = useAuthStore();
    
    // Auth প্রয়োজন এমন রুট
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login', query: { redirect: to.fullPath } });
        return;
    }
    
    // Guest only রুট
    if (to.meta.guestOnly && authStore.isAuthenticated) {
        next({ name: 'dashboard' });
        return;
    }
    
    // Admin রুট
    if (to.meta.requiresAdmin && !authStore.isAdmin) {
        next({ name: 'dashboard' });
        return;
    }
    
    // Permission চেক
    if (to.meta.permission && !authStore.hasPermission(to.meta.permission)) {
        next({ name: 'dashboard' });
        return;
    }
    
    next();
});

router.afterEach((to, from) => {
    NProgress.done();
    
    // পেজ টাইটেল
    document.title = to.meta.title 
        ? `${to.meta.title} - ${window.Laravel?.appName || 'Toolkit Pro'}`
        : window.Laravel?.appName || 'Toolkit Pro';
});

// ===================
// i18n Configuration
// ===================
const i18n = createI18n({
    legacy: false,
    locale: window.Laravel?.locale || 'bn',
    fallbackLocale: 'en',
    messages,
    globalInjection: true,
});

// ===================
// Pinia Configuration
// ===================
const pinia = createPinia();

// Pinia Plugins
pinia.use(({ store }) => {
    store.$subscribe((mutation, state) => {
        // স্টেট পরিবর্তন লগ (শুধুমাত্র ডেভেলপমেন্টে)
        if (import.meta.env.DEV) {
            console.log(`[${store.$id}]`, mutation.type, mutation.payload);
        }
    });
});

// ===================
// Head Management
// ===================
const head = createHead();

// ===================
// Utility Functions
// ===================
function generateRequestId() {
    return 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

// ===================
// Global Properties
// ===================
const globalProperties = {
    $axios: axios,
    $formatDate: (date, format = 'd/m/Y') => {
        if (!date) return null;
        const d = new Date(date);
        return d.toLocaleDateString(window.Laravel?.locale || 'bn-BD');
    },
    $formatNumber: (number) => {
        return new Intl.NumberFormat(window.Laravel?.locale || 'bn-BD').format(number);
    },
    $formatMoney: (amount, currency = 'USD') => {
        return new Intl.NumberFormat(window.Laravel?.locale || 'bn-BD', {
            style: 'currency',
            currency,
        }).format(amount);
    },
};

// ===================
// Create Application
// ===================
const app = createApp({
    setup() {
        // স্টোর ইনিশিয়ালাইজ
        const authStore = useAuthStore();
        const settingsStore = useSettingsStore();
        
        // ইউজার লোড
        if (authStore.isAuthenticated) {
            authStore.fetchUser();
        }
        
        // সেটিংস লোড
        settingsStore.fetchSettings();
        
        return () => h(App);
    },
});

// ===================
// Register Components
// ===================
app.component('ToolCard', ToolCard);
app.component('SearchBar', SearchBar);
app.component('Dashboard', Dashboard);

// ===================
// Register Plugins
// ===================
app.use(pinia);
app.use(router);
app.use(i18n);
app.use(head);

// ===================
// Register Global Properties
// ===================
Object.entries(globalProperties).forEach(([key, value]) => {
    app.config.globalProperties[key] = value;
});

// ===================
// Error Handler
// ===================
app.config.errorHandler = (err, instance, info) => {
    console.error('Vue Error:', err);
    console.error('Component:', instance);
    console.error('Info:', info);
    
    // Sentry রিপোর্টিং
    if (window.Sentry) {
        Sentry.captureException(err);
    }
};

// ===================
// Performance Monitoring
// ===================
if (import.meta.env.PROD) {
    // Web Vitals
    import('web-vitals').then(({ getCLS, getFID, getFCP, getLCP, getTTFB }) => {
        getCLS(console.log);
        getFID(console.log);
        getFCP(console.log);
        getLCP(console.log);
        getTTFB(console.log);
    });
}

// ===================
// Mount Application
// ===================
app.mount('#app');

// ===================
// Export for Testing
// ===================
export { app, router, pinia, i18n, head };
