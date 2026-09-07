const mix = require('laravel-mix');
const path = require('path');
const CompressionPlugin = require('compression-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const ImageMinimizerPlugin = require('image-minimizer-webpack-plugin');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

/*
|--------------------------------------------------------------------------
| Mix Asset Management
|--------------------------------------------------------------------------
|
| Mix provides a clean, fluent API for defining some Webpack build steps
| for your Laravel applications. By default, we are compiling the CSS
| file for the application as well as bundling up all the JS files.
|
| Toolkit Pro v2.0 - বিশ্বের সবচেয়ে সম্পূর্ণ অনলাইন টুলস প্ল্যাটফর্ম
|
*/

// ===================
// Basic Configuration
// ===================
mix.setPublicPath('public')
    .setResourceRoot('../')
    .options({
        processCssUrls: true,
        terser: {
            extractComments: false,
            terserOptions: {
                compress: {
                    drop_console: mix.inProduction(),
                    drop_debugger: mix.inProduction(),
                    pure_funcs: ['console.log'],
                },
                output: {
                    comments: false,
                },
            },
        },
        autoprefixer: {
            options: {
                browsers: [
                    '> 1%',
                    'last 2 versions',
                    'not dead',
                    'not ie 11',
                ],
            },
        },
    });

// ===================
// JavaScript Files
// ===================

// Main Application
mix.js('resources/js/app.js', 'public/js')
    .vue({ version: 3 })
    .extract(['vue', 'vue-router', 'pinia', 'axios']);

// Admin Panel
mix.js('resources/js/admin.js', 'public/js')
    .vue({ version: 3 })
    .extract(['vue', 'vue-router', 'pinia', 'axios', 'echarts']);

// Dashboard
mix.js('resources/js/dashboard.js', 'public/js')
    .vue({ version: 3 })
    .extract(['chart.js', 'echarts']);

// Tools Page
mix.js('resources/js/tools.js', 'public/js')
    .vue({ version: 3 });

// Marketplace
mix.js('resources/js/marketplace.js', 'public/js')
    .vue({ version: 3 })
    .extract(['web3']);

// Blockchain
mix.js('resources/js/blockchain.js', 'public/js')
    .extract(['web3']);

// ===================
// CSS Files
// ===================

// Main CSS
mix.sass('resources/sass/app.scss', 'public/css')
    .options({
        sassOptions: {
            outputStyle: mix.inProduction() ? 'compressed' : 'expanded',
            includePaths: [
                path.resolve(__dirname, 'node_modules'),
                path.resolve(__dirname, 'resources/sass'),
            ],
        },
    });

// Admin CSS
mix.sass('resources/sass/admin.scss', 'public/css');

// Dashboard CSS
mix.sass('resources/sass/dashboard.scss', 'public/css');

// PostCSS
mix.postCss('resources/css/app.css', 'public/css', [
    require('postcss-import'),
    require('tailwindcss/nesting'),
    require('tailwindcss'),
    require('autoprefixer'),
    require('postcss-preset-env')({
        stage: 3,
        features: {
            'nesting-rules': true,
            'custom-media-queries': true,
            'custom-properties': true,
        },
    }),
    require('cssnano')({
        preset: ['default', {
            discardComments: {
                removeAll: true,
            },
            normalizeWhitespace: true,
            minifyFontValues: true,
            minifySelectors: true,
            mergeLonghand: true,
        }],
    }),
]);

// ===================
// Copy Assets
// ===================

// Images
mix.copyDirectory('resources/images', 'public/images');

// Fonts
mix.copyDirectory('resources/fonts', 'public/fonts');

// Icons
mix.copyDirectory('resources/icons', 'public/icons');

// Favicon
mix.copy('resources/favicon.ico', 'public/favicon.ico');
mix.copy('resources/robots.txt', 'public/robots.txt');

// ===================
// Version Control (Cache Busting)
// ===================
if (mix.inProduction()) {
    mix.version();
}

// ===================
// Source Maps
// ===================
if (!mix.inProduction()) {
    mix.sourceMaps(true, 'source-map');
    mix.webpackConfig({
        devtool: 'eval-cheap-module-source-map',
    });
} else {
    mix.webpackConfig({
        devtool: false,
    });
}

// ===================
// Webpack Configuration
// ===================
mix.webpackConfig({
    // ===================
    // Resolve
    // ===================
    resolve: {
        extensions: ['.js', '.vue', '.json', '.jsx', '.ts', '.tsx'],
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '@components': path.resolve(__dirname, 'resources/js/components'),
            '@composables': path.resolve(__dirname, 'resources/js/composables'),
            '@layouts': path.resolve(__dirname, 'resources/js/layouts'),
            '@pages': path.resolve(__dirname, 'resources/js/pages'),
            '@stores': path.resolve(__dirname, 'resources/js/stores'),
            '@utils': path.resolve(__dirname, 'resources/js/utils'),
            '@assets': path.resolve(__dirname, 'resources/assets'),
            '@css': path.resolve(__dirname, 'resources/css'),
            '@sass': path.resolve(__dirname, 'resources/sass'),
            '@images': path.resolve(__dirname, 'resources/images'),
            '@fonts': path.resolve(__dirname, 'resources/fonts'),
            'vue$': 'vue/dist/vue.esm-bundler.js',
        },
        fallback: {
            'crypto': require.resolve('crypto-browserify'),
            'stream': require.resolve('stream-browserify'),
            'buffer': require.resolve('buffer/'),
            'util': require.resolve('util/'),
            'process': require.resolve('process/browser'),
            'path': require.resolve('path-browserify'),
            'os': require.resolve('os-browserify/browser'),
            'fs': false,
            'net': false,
            'tls': false,
        },
    },

    // ===================
    // Module Rules
    // ===================
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader',
                options: {
                    compilerOptions: {
                        compatConfig: {
                            MODE: 3,
                        },
                    },
                },
            },
            {
                test: /\.m?js$/,
                exclude: /(node_modules|bower_components)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: [
                            ['@babel/preset-env', {
                                targets: {
                                    browsers: ['> 1%', 'last 2 versions', 'not dead'],
                                },
                                useBuiltIns: 'usage',
                                corejs: 3,
                                modules: false,
                            }],
                        ],
                        plugins: [
                            '@babel/plugin-syntax-dynamic-import',
                            '@babel/plugin-proposal-class-properties',
                            '@babel/plugin-proposal-object-rest-spread',
                            '@babel/plugin-transform-runtime',
                        ],
                        cacheDirectory: true,
                        cacheCompression: false,
                    },
                },
            },
            {
                test: /\.(png|jpe?g|gif|svg|webp|ico)$/,
                type: 'asset',
                parser: {
                    dataUrlCondition: {
                        maxSize: 8 * 1024, // 8kb
                    },
                },
                generator: {
                    filename: 'images/[name].[contenthash:8][ext]',
                },
            },
            {
                test: /\.(woff|woff2|eot|ttf|otf)$/,
                type: 'asset/resource',
                generator: {
                    filename: 'fonts/[name].[contenthash:8][ext]',
                },
            },
            {
                test: /\.(mp4|webm|ogg|mp3|wav|flac|aac)$/,
                type: 'asset/resource',
                generator: {
                    filename: 'media/[name].[contenthash:8][ext]',
                },
            },
        ],
    },

    // ===================
    // Plugins
    // ===================
    plugins: [
        // Webpack Define Plugin
        new (require('webpack').DefinePlugin)({
            __VUE_OPTIONS_API__: true,
            __VUE_PROD_DEVTOOLS__: false,
            __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
            'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV),
            'process.env.MIX_APP_URL': JSON.stringify(process.env.MIX_APP_URL || 'http://localhost'),
            'process.env.MIX_APP_ENV': JSON.stringify(process.env.MIX_APP_ENV || 'production'),
        }),

        // Bundle Analyzer (শুধুমাত্র বিশ্লেষণের জন্য)
        ...(process.env.ANALYZE === 'true' ? [
            new BundleAnalyzerPlugin({
                analyzerMode: 'static',
                reportFilename: 'bundle-report.html',
                openAnalyzer: false,
            }),
        ] : []),

        // Compression Plugin (Production)
        ...(mix.inProduction() ? [
            new CompressionPlugin({
                algorithm: 'gzip',
                test: /\.(js|css|html|svg)$/,
                threshold: 10240,
                minRatio: 0.8,
                deleteOriginalAssets: false,
            }),
            new CompressionPlugin({
                algorithm: 'brotliCompress',
                filename: '[path][base].br',
                test: /\.(js|css|html|svg)$/,
                threshold: 10240,
                minRatio: 0.8,
                deleteOriginalAssets: false,
            }),
        ] : []),

        // Provide Plugin for Global Variables
        new (require('webpack').ProvidePlugin)({
            $: 'jquery',
            jQuery: 'jquery',
            'window.jQuery': 'jquery',
            _: 'lodash',
            axios: 'axios',
            moment: 'moment',
            process: 'process/browser',
            Buffer: ['buffer', 'Buffer'],
        }),
    ],

    // ===================
    // Optimization
    // ===================
    optimization: {
        minimize: mix.inProduction(),
        minimizer: [
            new TerserPlugin({
                terserOptions: {
                    parse: {
                        ecma: 2020,
                    },
                    compress: {
                        ecma: 5,
                        warnings: false,
                        comparisons: false,
                        inline: 2,
                        drop_console: true,
                        drop_debugger: true,
                    },
                    mangle: {
                        safari10: true,
                    },
                    output: {
                        ecma: 5,
                        comments: false,
                        ascii_only: true,
                    },
                },
                parallel: true,
                extractComments: false,
            }),
            new CssMinimizerPlugin({
                minimizerOptions: {
                    preset: [
                        'default',
                        {
                            discardComments: { removeAll: true },
                            normalizeWhitespace: true,
                            minifyFontValues: true,
                            minifySelectors: true,
                            mergeLonghand: true,
                            reduceTransforms: true,
                            svgo: true,
                        },
                    ],
                },
                parallel: true,
            }),
        ],
        splitChunks: {
            chunks: 'all',
            minSize: 20000,
            maxSize: 244000,
            minChunks: 1,
            maxAsyncRequests: 30,
            maxInitialRequests: 30,
            automaticNameDelimiter: '~',
            cacheGroups: {
                defaultVendors: {
                    test: /[\\/]node_modules[\\/]/,
                    priority: -10,
                    reuseExistingChunk: true,
                },
                default: {
                    minChunks: 2,
                    priority: -20,
                    reuseExistingChunk: true,
                },
                vue: {
                    test: /[\\/]node_modules[\\/](vue|vue-router|pinia)[\\/]/,
                    name: 'vue',
                    chunks: 'all',
                    priority: 20,
                },
                axios: {
                    test: /[\\/]node_modules[\\/]axios[\\/]/,
                    name: 'axios',
                    chunks: 'all',
                    priority: 20,
                },
                lodash: {
                    test: /[\\/]node_modules[\\/]lodash[\\/]/,
                    name: 'lodash',
                    chunks: 'all',
                    priority: 20,
                },
                charts: {
                    test: /[\\/]node_modules[\\/](chart\.js|echarts)[\\/]/,
                    name: 'charts',
                    chunks: 'all',
                    priority: 20,
                },
                web3: {
                    test: /[\\/]node_modules[\\/]web3[\\/]/,
                    name: 'web3',
                    chunks: 'all',
                    priority: 20,
                },
            },
        },
        runtimeChunk: 'single',
        moduleIds: 'deterministic',
        chunkIds: 'deterministic',
    },

    // ===================
    // Performance
    // ===================
    performance: {
        hints: mix.inProduction() ? 'warning' : false,
        maxEntrypointSize: 512000,
        maxAssetSize: 512000,
    },

    // ===================
    // Stats
    // ===================
    stats: {
        colors: true,
        modules: false,
        children: false,
        chunks: false,
        chunkModules: false,
        assets: true,
        errors: true,
        warnings: true,
        hash: false,
        version: false,
        timings: true,
        builtAt: true,
        entrypoints: true,
        performance: true,
    },

    // ===================
    // Watch Options
    // ===================
    watchOptions: {
        ignored: /node_modules/,
        aggregateTimeout: 300,
        poll: 1000,
    },

    // ===================
    // Dev Server
    // ===================
    devServer: {
        host: 'localhost',
        port: 8080,
        hot: true,
        compress: true,
        liveReload: true,
        open: false,
        historyApiFallback: true,
        static: {
            directory: path.join(__dirname, 'public'),
            publicPath: '/',
        },
        client: {
            logging: 'info',
            overlay: {
                errors: true,
                warnings: false,
            },
            progress: true,
        },
        headers: {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            'Access-Control-Allow-Headers': 'X-Requested-With, content-type, Authorization',
        },
        proxy: [
            {
                context: ['/api'],
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false,
            },
        ],
    },
});

// ===================
// Browser Sync
// ===================
if (!mix.inProduction()) {
    mix.browserSync({
        proxy: 'localhost:8000',
        files: [
            'app/**/*.php',
            'resources/views/**/*.php',
            'resources/js/**/*.vue',
            'resources/js/**/*.js',
            'resources/css/**/*.css',
            'resources/sass/**/*.scss',
            'config/**/*.php',
            'routes/**/*.php',
        ],
        open: false,
        notify: false,
        ghostMode: {
            clicks: false,
            scroll: false,
            forms: false,
        },
        snippetOptions: {
            rule: {
                match: /<\/body>/i,
                fn: function (snippet, match) {
                    return snippet + match;
                },
            },
        },
    });
}

// ===================
// Disable Notifications
// ===================
mix.disableNotifications();

// ===================
// Custom Tasks
// ===================

// Clean build directory
mix.before(async () => {
    const fs = require('fs-extra');
    await fs.emptyDir('public/js');
    await fs.emptyDir('public/css');
    await fs.emptyDir('public/images');
    await fs.emptyDir('public/fonts');
    await fs.emptyDir('public/icons');
});

// Generate build report
mix.after(async (stats) => {
    if (process.env.BUILD_REPORT === 'true') {
        const fs = require('fs-extra');
        const report = {
            buildTime: new Date().toISOString(),
            hash: stats.hash,
            assets: stats.toJson().assets.map(asset => ({
                name: asset.name,
                size: asset.size,
                type: asset.type,
            })),
            warnings: stats.toJson().warnings,
            errors: stats.toJson().errors,
        };
        await fs.writeJson('public/build-report.json', report, { spaces: 2 });
    }
});
