/** @type {import('tailwindcss').Config} */

import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import aspectRatio from '@tailwindcss/aspect-ratio';
import containerQueries from '@tailwindcss/container-queries';

export default {
    // ===================
    // Content Sources
    // ===================
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
        './resources/js/**/*.jsx',
        './resources/js/**/*.ts',
        './resources/js/**/*.tsx',
        './resources/css/**/*.css',
        './resources/css/**/*.scss',
        './node_modules/@headlessui/vue/dist/*.js',
        './node_modules/@heroicons/vue/**/*.js',
        './node_modules/vue-multiselect/dist/*.js',
    ],

    // ===================
    // Dark Mode
    // ===================
    darkMode: 'class',

    // ===================
    // Theme Configuration
    // ===================
    theme: {
        // ===================
        // Container
        // ===================
        container: {
            center: true,
            padding: {
                DEFAULT: '1rem',
                sm: '2rem',
                lg: '4rem',
                xl: '5rem',
                '2xl': '6rem',
            },
            screens: {
                sm: '640px',
                md: '768px',
                lg: '1024px',
                xl: '1280px',
                '2xl': '1536px',
            },
        },

        // ===================
        // Screen Breakpoints
        // ===================
        screens: {
            xs: '475px',
            ...defaultTheme.screens,
            '3xl': '1920px',
            '4xl': '2560px',
        },

        // ===================
        // Colors
        // ===================
        colors: {
            transparent: 'transparent',
            current: 'currentColor',
            black: colors.black,
            white: colors.white,
            gray: colors.gray,
            zinc: colors.zinc,
            neutral: colors.neutral,
            stone: colors.stone,
            red: colors.red,
            orange: colors.orange,
            amber: colors.amber,
            yellow: colors.yellow,
            lime: colors.lime,
            green: colors.green,
            emerald: colors.emerald,
            teal: colors.teal,
            cyan: colors.cyan,
            sky: colors.sky,
            blue: colors.blue,
            indigo: colors.indigo,
            violet: colors.violet,
            purple: colors.purple,
            fuchsia: colors.fuchsia,
            pink: colors.pink,
            rose: colors.rose,
            slate: colors.slate,

            // Custom Colors
            primary: {
                50: '#eef2ff',
                100: '#e0e7ff',
                200: '#c7d2fe',
                300: '#a5b4fc',
                400: '#818cf8',
                500: '#6366f1',
                600: '#4f46e5',
                700: '#4338ca',
                800: '#3730a3',
                900: '#312e81',
                950: '#1e1b4b',
            },
            secondary: {
                50: '#f0fdfa',
                100: '#ccfbf1',
                200: '#99f6e4',
                300: '#5eead4',
                400: '#2dd4bf',
                500: '#14b8a6',
                600: '#0d9488',
                700: '#0f766e',
                800: '#115e59',
                900: '#134e4a',
                950: '#042f2e',
            },
            accent: {
                50: '#fff7ed',
                100: '#ffedd5',
                200: '#fed7aa',
                300: '#fdba74',
                400: '#fb923c',
                500: '#f97316',
                600: '#ea580c',
                700: '#c2410c',
                800: '#9a3412',
                900: '#7c2d12',
                950: '#431407',
            },
            success: {
                50: '#f0fdf4',
                100: '#dcfce7',
                200: '#bbf7d0',
                300: '#86efac',
                400: '#4ade80',
                500: '#22c55e',
                600: '#16a34a',
                700: '#15803d',
                800: '#166534',
                900: '#14532d',
                950: '#052e16',
            },
            warning: {
                50: '#fefce8',
                100: '#fef9c3',
                200: '#fef08a',
                300: '#fde047',
                400: '#facc15',
                500: '#eab308',
                600: '#ca8a04',
                700: '#a16207',
                800: '#854d0e',
                900: '#713f12',
                950: '#422006',
            },
            danger: {
                50: '#fef2f2',
                100: '#fee2e2',
                200: '#fecaca',
                300: '#fca5a5',
                400: '#f87171',
                500: '#ef4444',
                600: '#dc2626',
                700: '#b91c1c',
                800: '#991b1b',
                900: '#7f1d1d',
                950: '#450a0a',
            },
            info: {
                50: '#eff6ff',
                100: '#dbeafe',
                200: '#bfdbfe',
                300: '#93c5fd',
                400: '#60a5fa',
                500: '#3b82f6',
                600: '#2563eb',
                700: '#1d4ed8',
                800: '#1e40af',
                900: '#1e3a8a',
                950: '#172554',
            },
            dark: {
                50: '#f8fafc',
                100: '#f1f5f9',
                200: '#e2e8f0',
                300: '#cbd5e1',
                400: '#94a3b8',
                500: '#64748b',
                600: '#475569',
                700: '#334155',
                800: '#1e293b',
                900: '#0f172a',
                950: '#020617',
            },
        },

        // ===================
        // Typography
        // ===================
        fontFamily: {
            sans: [
                'Inter',
                'Noto Sans Bengali',
                'Hind Siliguri',
                'SolaimanLipi',
                'system-ui',
                '-apple-system',
                'Segoe UI',
                'Roboto',
                'Helvetica Neue',
                'Arial',
                'sans-serif',
            ],
            serif: [
                'Merriweather',
                'Noto Serif Bengali',
                'Georgia',
                'Cambria',
                'Times New Roman',
                'serif',
            ],
            mono: [
                'JetBrains Mono',
                'Fira Code',
                'Cascadia Code',
                'SF Mono',
                'Monaco',
                'Consolas',
                'Liberation Mono',
                'Courier New',
                'monospace',
            ],
            display: [
                'Poppins',
                'Inter',
                'system-ui',
                'sans-serif',
            ],
            body: [
                'Inter',
                'Noto Sans Bengali',
                'system-ui',
                'sans-serif',
            ],
        },

        // ===================
        // Font Sizes
        // ===================
        fontSize: {
            xs: ['0.75rem', { lineHeight: '1rem' }],
            sm: ['0.875rem', { lineHeight: '1.25rem' }],
            base: ['1rem', { lineHeight: '1.5rem' }],
            lg: ['1.125rem', { lineHeight: '1.75rem' }],
            xl: ['1.25rem', { lineHeight: '1.75rem' }],
            '2xl': ['1.5rem', { lineHeight: '2rem' }],
            '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
            '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
            '5xl': ['3rem', { lineHeight: '1' }],
            '6xl': ['3.75rem', { lineHeight: '1' }],
            '7xl': ['4.5rem', { lineHeight: '1' }],
            '8xl': ['6rem', { lineHeight: '1' }],
            '9xl': ['8rem', { lineHeight: '1' }],
        },

        // ===================
        // Font Weights
        // ===================
        fontWeight: {
            thin: '100',
            extralight: '200',
            light: '300',
            normal: '400',
            medium: '500',
            semibold: '600',
            bold: '700',
            extrabold: '800',
            black: '900',
        },

        // ===================
        // Line Heights
        // ===================
        lineHeight: {
            none: '1',
            tight: '1.25',
            snug: '1.375',
            normal: '1.5',
            relaxed: '1.625',
            loose: '2',
            3: '.75rem',
            4: '1rem',
            5: '1.25rem',
            6: '1.5rem',
            7: '1.75rem',
            8: '2rem',
            9: '2.25rem',
            10: '2.5rem',
        },

        // ===================
        // Letter Spacing
        // ===================
        letterSpacing: {
            tighter: '-0.05em',
            tight: '-0.025em',
            normal: '0em',
            wide: '0.025em',
            wider: '0.05em',
            widest: '0.1em',
        },

        // ===================
        // Spacing
        // ===================
        spacing: {
            px: '1px',
            0: '0px',
            0.5: '0.125rem',
            1: '0.25rem',
            1.5: '0.375rem',
            2: '0.5rem',
            2.5: '0.625rem',
            3: '0.75rem',
            3.5: '0.875rem',
            4: '1rem',
            5: '1.25rem',
            6: '1.5rem',
            7: '1.75rem',
            8: '2rem',
            9: '2.25rem',
            10: '2.5rem',
            11: '2.75rem',
            12: '3rem',
            14: '3.5rem',
            16: '4rem',
            20: '5rem',
            24: '6rem',
            28: '7rem',
            32: '8rem',
            36: '9rem',
            40: '10rem',
            44: '11rem',
            48: '12rem',
            52: '13rem',
            56: '14rem',
            60: '15rem',
            64: '16rem',
            72: '18rem',
            80: '20rem',
            96: '24rem',
        },

        // ===================
        // Border Radius
        // ===================
        borderRadius: {
            none: '0',
            sm: '0.125rem',
            DEFAULT: '0.25rem',
            md: '0.375rem',
            lg: '0.5rem',
            xl: '0.75rem',
            '2xl': '1rem',
            '3xl': '1.5rem',
            full: '9999px',
        },

        // ===================
        // Shadows
        // ===================
        boxShadow: {
            sm: '0 1px 2px 0 rgb(0 0 0 / 0.05)',
            DEFAULT: '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
            md: '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
            lg: '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
            xl: '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)',
            '2xl': '0 25px 50px -12px rgb(0 0 0 / 0.25)',
            '3xl': '0 35px 60px -15px rgb(0 0 0 / 0.3)',
            inner: 'inset 0 2px 4px 0 rgb(0 0 0 / 0.05)',
            none: 'none',
            glow: '0 0 20px rgba(99, 102, 241, 0.5)',
            'glow-lg': '0 0 40px rgba(99, 102, 241, 0.6)',
            'glow-xl': '0 0 60px rgba(99, 102, 241, 0.7)',
        },

        // ===================
        // Animations
        // ===================
        animation: {
            none: 'none',
            spin: 'spin 1s linear infinite',
            ping: 'ping 1s cubic-bezier(0, 0, 0.2, 1) infinite',
            pulse: 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            bounce: 'bounce 1s infinite',
            'fade-in': 'fadeIn 0.5s ease-in-out',
            'fade-out': 'fadeOut 0.5s ease-in-out',
            'slide-in': 'slideIn 0.3s ease-out',
            'slide-out': 'slideOut 0.3s ease-in',
            'scale-in': 'scaleIn 0.2s ease-out',
            'scale-out': 'scaleOut 0.2s ease-in',
            shimmer: 'shimmer 2s linear infinite',
            'spin-slow': 'spin 3s linear infinite',
            'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        },

        // ===================
        // Keyframes
        // ===================
        keyframes: {
            fadeIn: {
                '0%': { opacity: '0' },
                '100%': { opacity: '1' },
            },
            fadeOut: {
                '0%': { opacity: '1' },
                '100%': { opacity: '0' },
            },
            slideIn: {
                '0%': { transform: 'translateX(-100%)' },
                '100%': { transform: 'translateX(0)' },
            },
            slideOut: {
                '0%': { transform: 'translateX(0)' },
                '100%': { transform: 'translateX(100%)' },
            },
            scaleIn: {
                '0%': { transform: 'scale(0.9)', opacity: '0' },
                '100%': { transform: 'scale(1)', opacity: '1' },
            },
            scaleOut: {
                '0%': { transform: 'scale(1)', opacity: '1' },
                '100%': { transform: 'scale(0.9)', opacity: '0' },
            },
            shimmer: {
                '0%': { backgroundPosition: '-1000px 0' },
                '100%': { backgroundPosition: '1000px 0' },
            },
        },

        // ===================
        // Transitions
        // ===================
        transitionDuration: {
            DEFAULT: '150ms',
            0: '0ms',
            75: '75ms',
            100: '100ms',
            150: '150ms',
            200: '200ms',
            300: '300ms',
            500: '500ms',
            700: '700ms',
            1000: '1000ms',
        },

        // ===================
        // Z-Index
        // ===================
        zIndex: {
            auto: 'auto',
            0: '0',
            10: '10',
            20: '20',
            30: '30',
            40: '40',
            50: '50',
            dropdown: '1000',
            sticky: '1020',
            fixed: '1030',
            modal: '1040',
            popover: '1050',
            tooltip: '1060',
            toast: '1070',
            loading: '1080',
            max: '9999',
        },
    },

    // ===================
    // Variants
    // ===================
    variants: {
        extend: {
            opacity: ['disabled'],
            cursor: ['disabled'],
            backgroundColor: ['active', 'disabled'],
            textColor: ['active', 'disabled'],
            borderColor: ['active', 'disabled'],
            ringWidth: ['hover', 'active'],
            ringColor: ['hover', 'active'],
            scale: ['active', 'group-hover'],
            rotate: ['active', 'group-hover'],
            translate: ['active', 'group-hover'],
            display: ['group-hover', 'group-focus'],
            visibility: ['group-hover', 'group-focus'],
            animation: ['hover', 'focus', 'group-hover'],
        },
    },

    // ===================
    // Plugins
    // ===================
    plugins: [
        forms,
        typography,
        aspectRatio,
        containerQueries,
        
        // Custom Plugin for Bengali Typography
        function ({ addUtilities, addComponents, addBase, theme }) {
            // Base Styles
            addBase({
                'html': {
                    scrollBehavior: 'smooth',
                    WebkitFontSmoothing: 'antialiased',
                    MozOsxFontSmoothing: 'grayscale',
                },
                'body': {
                    fontFamily: theme('fontFamily.body'),
                    fontSize: theme('fontSize.base'),
                    lineHeight: theme('lineHeight.normal'),
                    color: theme('colors.dark.800'),
                    backgroundColor: theme('colors.white'),
                },
                'h1, h2, h3, h4, h5, h6': {
                    fontFamily: theme('fontFamily.display'),
                    fontWeight: theme('fontWeight.bold'),
                    lineHeight: theme('lineHeight.tight'),
                },
            });

            // Custom Components
            addComponents({
                '.container-custom': {
                    maxWidth: '100%',
                    marginLeft: 'auto',
                    marginRight: 'auto',
                    paddingLeft: '1rem',
                    paddingRight: '1rem',
                    '@screen sm': {
                        paddingLeft: '2rem',
                        paddingRight: '2rem',
                    },
                    '@screen lg': {
                        paddingLeft: '4rem',
                        paddingRight: '4rem',
                    },
                },
                '.btn': {
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    padding: '0.5rem 1rem',
                    borderRadius: theme('borderRadius.md'),
                    fontWeight: theme('fontWeight.medium'),
                    transition: 'all 150ms ease',
                    '&:focus': {
                        outline: 'none',
                        ringWidth: '2px',
                        ringColor: theme('colors.primary.500'),
                    },
                },
                '.btn-primary': {
                    backgroundColor: theme('colors.primary.600'),
                    color: theme('colors.white'),
                    '&:hover': {
                        backgroundColor: theme('colors.primary.700'),
                    },
                },
                '.btn-secondary': {
                    backgroundColor: theme('colors.secondary.600'),
                    color: theme('colors.white'),
                    '&:hover': {
                        backgroundColor: theme('colors.secondary.700'),
                    },
                },
                '.card': {
                    backgroundColor: theme('colors.white'),
                    borderRadius: theme('borderRadius.lg'),
                    boxShadow: theme('boxShadow.md'),
                    overflow: 'hidden',
                },
                '.input': {
                    width: '100%',
                    padding: '0.5rem 0.75rem',
                    borderRadius: theme('borderRadius.md'),
                    borderWidth: '1px',
                    borderColor: theme('colors.gray.300'),
                    '&:focus': {
                        outline: 'none',
                        borderColor: theme('colors.primary.500'),
                        ringWidth: '2px',
                        ringColor: theme('colors.primary.200'),
                    },
                },
            });

            // Custom Utilities
            addUtilities({
                '.text-gradient': {
                    background: 'linear-gradient(to right, #6366f1, #8b5cf6)',
                    WebkitBackgroundClip: 'text',
                    WebkitTextFillColor: 'transparent',
                    backgroundClip: 'text',
                    color: 'transparent',
                },
                '.bg-gradient-primary': {
                    background: 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #2563eb 100%)',
                },
                '.bg-gradient-secondary': {
                    background: 'linear-gradient(135deg, #0d9488 0%, #0891b2 100%)',
                },
                '.backdrop-blur-sm': {
                    backdropFilter: 'blur(4px)',
                },
                '.backdrop-blur-md': {
                    backdropFilter: 'blur(8px)',
                },
                '.backdrop-blur-lg': {
                    backdropFilter: 'blur(16px)',
                },
                '.scrollbar-hide': {
                    scrollbarWidth: 'none',
                    '&::-webkit-scrollbar': {
                        display: 'none',
                    },
                },
                '.scrollbar-thin': {
                    scrollbarWidth: 'thin',
                    '&::-webkit-scrollbar': {
                        width: '6px',
                    },
                    '&::-webkit-scrollbar-thumb': {
                        backgroundColor: theme('colors.gray.300'),
                        borderRadius: '3px',
                    },
                },
            });
        },
    ],
};
