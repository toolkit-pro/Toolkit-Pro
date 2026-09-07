<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <title>@yield('title', config('app.name', 'Toolkit Pro'))</title>
    <meta name="description" content="@yield('meta_description', 'বিশ্বের সবচেয়ে সম্পূর্ণ ও শক্তিশালী অনলাইন টুলস প্ল্যাটফর্ম')">
    <meta name="keywords" content="@yield('meta_keywords', 'toolkit, online tools, ai tools, automation')">
    <meta name="author" content="Toolkit Pro">
    <meta name="robots" content="@yield('meta_robots', 'index, follow')">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', config('app.name', 'Toolkit Pro'))">
    <meta property="og:description" content="@yield('meta_description', 'বিশ্বের সবচেয়ে সম্পূর্ণ ও শক্তিশালী অনলাইন টুলস প্ল্যাটফর্ম')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-image.png'))">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('title', config('app.name', 'Toolkit Pro'))">
    <meta name="twitter:description" content="@yield('meta_description', 'বিশ্বের সবচেয়ে সম্পূর্ণ ও শক্তিশালী অনলাইন টুলস প্ল্যাটফর্ম')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/og-image.png'))">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#4F46E5">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Noto+Sans+Bengali:wght@300;400;500;600;700;800&family=Hind+Siliguri:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    @vite(['resources/css/app.css'])
    @stack('styles')
    
    <!-- Scripts (Head) -->
    @vite(['resources/js/app.js'])
    <script>
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}',
            userId: '{{ auth()->id() }}',
            user: @json(auth()->user()),
            locale: '{{ app()->getLocale() }}',
            appUrl: '{{ config('app.url') }}',
            apiUrl: '{{ config('app.url') }}/api/v1',
        };
    </script>
    @stack('head-scripts')
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 antialiased">
    <!-- Loading Screen -->
    <div id="loading-screen" class="fixed inset-0 z-50 flex items-center justify-center bg-white dark:bg-gray-900 transition-opacity duration-300">
        <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primary-600 mx-auto"></div>
            <p class="mt-4 text-gray-600 dark:text-gray-400">লোড হচ্ছে...</p>
        </div>
    </div>

    <!-- Navigation -->
    @include('partials.navbar')
    
    <!-- Sidebar (Dashboard) -->
    @if(request()->is('dashboard*') || request()->is('admin*'))
        @include('partials.sidebar')
    @endif

    <!-- Main Content -->
    <main class="@yield('main_class', 'min-h-screen')">
        <!-- Flash Messages -->
        @if(session()->has('success'))
            <div class="fixed top-20 right-4 z-50 animate-fade-in" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session()->has('error'))
            <div class="fixed top-20 right-4 z-50 animate-fade-in" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <div class="bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @if(session()->has('warning'))
            <div class="fixed top-20 right-4 z-50 animate-fade-in" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <div class="bg-yellow-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('warning') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    @include('partials.footer')

    <!-- Scripts (Body) -->
    @stack('scripts')
    
    <script>
        // Loading screen hide
        window.addEventListener('load', function() {
            const loadingScreen = document.getElementById('loading-screen');
            if (loadingScreen) {
                loadingScreen.style.opacity = '0';
                setTimeout(() => {
                    loadingScreen.style.display = 'none';
                }, 300);
            }
        });
    </script>
</body>
</html>
