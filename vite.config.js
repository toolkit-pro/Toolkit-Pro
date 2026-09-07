import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';
import compression from 'vite-plugin-compression';
import eslint from 'vite-plugin-eslint';
import vueDevTools from 'vite-plugin-vue-devtools';
import { fileURLToPath, URL } from 'node:url';
import path from 'path';

export default defineConfig(({ mode }) => {
    // এনভায়রনমেন্ট ভেরিয়েবল লোড করুন
    const env = loadEnv(mode, process.cwd(), '');
    
    return {
        // ===================
        // Plugins
        // ===================
        plugins: [
            // Laravel Plugin
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.js',
                    'resources/js/admin.js',
                    'resources/js/dashboard.js',
                    'resources/js/tools.js',
                    'resources/js/marketplace.js'
                ],
                refresh: true,
                detectTls: env.APP_URL ? env.APP_URL.includes('https') : false,
            }),
            
            // Vue 3 Plugin
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
                script: {
                    defineModel: true,
                    propsDestructure: true,
                },
            }),
            
            // PWA Plugin
            VitePWA({
                registerType: 'autoUpdate',
                includeAssets: ['favicon.ico', 'robots.txt', 'apple-touch-icon.png'],
                manifest: {
                    name: 'Toolkit Pro',
                    short_name: 'Toolkit',
                    description: 'বিশ্বের সবচেয়ে সম্পূর্ণ অনলাইন টুলস প্ল্যাটফর্ম',
                    theme_color: '#4F46E5',
                    background_color: '#ffffff',
                    display: 'standalone',
                    orientation: 'portrait',
                    scope: '/',
                    start_url: '/',
                    icons: [
                        {
                            src: '/icons/icon-72x72.png',
                            sizes: '72x72',
                            type: 'image/png',
                        },
                        {
                            src: '/icons/icon-96x96.png',
                            sizes: '96x96',
                            type: 'image/png',
                        },
                        {
                            src: '/icons/icon-128x128.png',
                            sizes: '128x128',
                            type: 'image/png',
                        },
                        {
                            src: '/icons/icon-144x144.png',
                            sizes: '144x144',
                            type: 'image/png',
                        },
                        {
                            src: '/icons/icon-152x152.png',
                            sizes: '152x152',
                            type: 'image/png',
                        },
                        {
                            src: '/icons/icon-192x192.png',
                            sizes: '192x192',
                            type: 'image/png',
                        },
                        {
                            src: '/icons/icon-384x384.png',
                            sizes: '384x384',
                            type: 'image/png',
                        },
                        {
                            src: '/icons/icon-512x512.png',
                            sizes: '512x512',
                            type: 'image/png',
                        },
                        {
                            src: '/icons/maskable-icon-512x512.png',
                            sizes: '512x512',
                            type: 'image/png',
                            purpose: 'maskable',
                        },
                    ],
                },
                workbox: {
                    globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
                    runtimeCaching: [
                        {
                            urlPattern: /^https:\/\/api\.toolkitpro\.com\/.*/i,
                            handler: 'NetworkFirst',
                            options: {
                                cacheName: 'api-cache',
                                expiration: {
                                    maxEntries: 100,
                                    maxAgeSeconds: 60 * 60 * 24, // 24 hours
                                },
                                cacheableResponse: {
                                    statuses: [0, 200],
                                },
                            },
                        },
                        {
                            urlPattern: /^https:\/\/cdn\.toolkitpro\.com\/.*/i,
                            handler: 'CacheFirst',
                            options: {
                                cacheName: 'cdn-cache',
                                expiration: {
                                    maxEntries: 200,
                                    maxAgeSeconds: 60 * 60 * 24 * 30, // 30 days
                                },
                            },
                        },
                    ],
                },
            }),
            
            // Compression Plugin
            compression({
                algorithm: 'gzip',
                ext: '.gz',
                threshold: 10240,
                deleteOriginFile: false,
            }),
            compression({
                algorithm: 'brotliCompress',
                ext: '.br',
                threshold: 10240,
                deleteOriginFile: false,
            }),
            
            // ESLint Plugin
            eslint({
                include: ['resources/js/**/*.{js,vue}'],
                exclude: ['node_modules/**'],
                fix: true,
                cache: false,
            }),
            
            // Vue DevTools (শুধুমাত্র ডেভেলপমেন্টে)
            ...(mode === 'development' ? [vueDevTools()] : []),
        ],
        
        // ===================
        // Resolve Configuration
        // ===================
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
                '@components': fileURLToPath(new URL('./resources/js/components', import.meta.url)),
                '@composables': fileURLToPath(new URL('./resources/js/composables', import.meta.url)),
                '@layouts': fileURLToPath(new URL('./resources/js/layouts', import.meta.url)),
                '@pages': fileURLToPath(new URL('./resources/js/pages', import.meta.url)),
                '@stores': fileURLToPath(new URL('./resources/js/stores', import.meta.url)),
                '@utils': fileURLToPath(new URL('./resources/js/utils', import.meta.url)),
                '@assets': fileURLToPath(new URL('./resources/assets', import.meta.url)),
                '@css': fileURLToPath(new URL('./resources/css', import.meta.url)),
                '~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
                '~bootstrap-icons': path.resolve(__dirname, 'node_modules/bootstrap-icons'),
            },
        },
        
        // ===================
        // Build Configuration
        // ===================
        build: {
            outDir: 'public/build',
            assetsDir: 'assets',
            manifest: true,
            sourcemap: mode === 'development',
            minify: mode === 'production' ? 'esbuild' : false,
            cssCodeSplit: true,
            chunkSizeWarningLimit: 2000,
            rollupOptions: {
                output: {
                    manualChunks: {
                        vendor: ['vue', 'vue-router', 'pinia'],
                        axios: ['axios'],
                        lodash: ['lodash-es'],
                        charts: ['chart.js', 'echarts'],
                        editor: ['quill', 'codemirror'],
                        utils: ['dayjs', 'moment', 'nprogress'],
                    },
                },
            },
            target: 'es2018',
            reportCompressedSize: true,
        },
        
        // ===================
        // Server Configuration
        // ===================
        server: {
            host: true,
            port: 5173,
            strictPort: true,
            https: env.VITE_HTTPS === 'true',
            hmr: {
                host: 'localhost',
                protocol: 'ws',
                port: 5173,
            },
            watch: {
                usePolling: true,
                interval: 1000,
            },
            cors: {
                origin: '*',
                methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
                allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With'],
                credentials: true,
            },
            proxy: {
                '/api': {
                    target: env.VITE_API_URL || 'http://localhost:8080',
                    changeOrigin: true,
                    secure: false,
                    rewrite: (path) => path.replace(/^\/api/, '/api'),
                },
                '/storage': {
                    target: env.VITE_STORAGE_URL || 'http://localhost:8080',
                    changeOrigin: true,
                    secure: false,
                },
                '/ws': {
                    target: env.VITE_WS_URL || 'ws://localhost:6001',
                    ws: true,
                    changeOrigin: true,
                    secure: false,
                },
            },
        },
        
        // ===================
        // Preview Configuration
        // ===================
        preview: {
            host: true,
            port: 4173,
            strictPort: true,
            https: env.VITE_HTTPS === 'true',
        },
        
        // ===================
        // CSS Configuration
        // ===================
        css: {
            preprocessorOptions: {
                scss: {
                    additionalData: `@import "@/assets/scss/variables.scss"; @import "@/assets/scss/mixins.scss";`,
                    quietDeps: true,
                },
                less: {
                    javascriptEnabled: true,
                },
            },
            postcss: {
                plugins: [
                    require('autoprefixer'),
                    require('tailwindcss'),
                    require('postcss-nesting'),
                    require('postcss-preset-env')({
                        stage: 3,
                        features: {
                            'nesting-rules': true,
                        },
                    }),
                ],
            },
            devSourcemap: true,
        },
        
        // ===================
        // Optimization
        // ===================
        optimizeDeps: {
            include: [
                'vue',
                'vue-router',
                'pinia',
                'axios',
                'lodash-es',
                '@vueuse/core',
                'chart.js',
                'echarts',
            ],
            exclude: ['@inertiajs/vue3'],
        },
        
        // ===================
        // Environment Variables
        // ===================
        define: {
            __VUE_OPTIONS_API__: true,
            __VUE_PROD_DEVTOOLS__: false,
            __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
            'process.env': env,
        },
        
        // ===================
        // Logging
        // ===================
        logLevel: 'info',
        clearScreen: false,
        
        // ===================
        // Public Directory
        // ===================
        publicDir: 'public',
        
        // ===================
        // Cache Directory
        // ===================
        cacheDir: 'node_modules/.vite',
    };
});
