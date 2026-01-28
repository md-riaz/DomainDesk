<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? (partnerBranding()?->email_sender_name ?? config('app.name', 'DomainDesk')) }}</title>

        <!-- Favicon -->
        @if(partnerBranding()?->favicon_path)
            <link rel="icon" type="image/x-icon" href="{{ Storage::url(partnerBranding()->favicon_path) }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Dynamic Partner Colors -->
        @if(partnerBranding())
        <style>
            :root {
                --partner-primary: {{ partnerBranding()->primary_color ?? '#3b82f6' }};
                --partner-secondary: {{ partnerBranding()->secondary_color ?? '#8b5cf6' }};
            }
        </style>
        @endif

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
        @endif
    </head>
    <body class="bg-gray-50 dark:bg-gray-900 antialiased min-h-screen flex flex-col">
        <div class="flex-1 flex">
            <!-- Sidebar -->
            <aside class="hidden md:flex md:flex-shrink-0">
                <div class="flex flex-col w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
                    <!-- Logo -->
                    <div class="flex items-center h-16 flex-shrink-0 px-4 border-b border-gray-200 dark:border-gray-700">
                        @if(partnerBranding()?->logo_path)
                            <img src="{{ Storage::url(partnerBranding()->logo_path) }}" 
                                 alt="{{ partnerBranding()->email_sender_name ?? 'Logo' }}" 
                                 class="h-8 w-auto">
                        @else
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ partnerBranding()?->email_sender_name ?? config('app.name', 'DomainDesk') }}
                            </span>
                        @endif
                    </div>

                    <!-- Navigation -->
                    <nav class="flex-1 px-2 py-4 space-y-1">
                        <a href="{{ route('client.dashboard') }}" 
                           class="group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('client.dashboard') ? 'bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }}">
                            <svg class="mr-3 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Dashboard
                        </a>
                        <a href="{{ route('client.domains.search') }}" 
                           class="group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('client.domains.search') ? 'bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }}">
                            <svg class="mr-3 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Search Domains
                        </a>
                        <a href="{{ route('client.domains.list') }}" 
                           class="group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('client.domains.list') || request()->routeIs('client.domains.show') ? 'bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }}">
                            <svg class="mr-3 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                            </svg>
                            My Domains
                        </a>
                        <a href="{{ route('client.invoices.list') }}" 
                           class="group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('client.invoices.*') ? 'bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }}">
                            <svg class="mr-3 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Invoices
                        </a>
                    </nav>

                    <!-- User Menu -->
                    <div class="flex-shrink-0 flex border-t border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex-shrink-0 w-full group block">
                            <div class="flex items-center">
                                <div class="ml-3 flex-1">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ auth()->user()->name }}
                                    </p>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        {{ auth()->user()->email }}
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="ml-3 inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Mobile menu button -->
            <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 z-50">
                <nav class="flex justify-around py-2">
                    <a href="{{ route('client.dashboard') }}" class="flex flex-col items-center px-3 py-2 text-xs {{ request()->routeIs('client.dashboard') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }}">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="mt-1">Home</span>
                    </a>
                    <a href="{{ route('client.domains.search') }}" class="flex flex-col items-center px-3 py-2 text-xs {{ request()->routeIs('client.domains.search') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }}">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span class="mt-1">Search</span>
                    </a>
                    <a href="{{ route('client.domains.list') }}" class="flex flex-col items-center px-3 py-2 text-xs {{ request()->routeIs('client.domains.list') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }}">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                        <span class="mt-1">Domains</span>
                    </a>
                    <a href="{{ route('client.invoices.list') }}" class="flex flex-col items-center px-3 py-2 text-xs {{ request()->routeIs('client.invoices.*') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }}">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="mt-1">Invoices</span>
                    </a>
                </nav>
            </div>

            <!-- Main content -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Top bar for mobile -->
                <div class="md:hidden bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                    <div class="flex items-center justify-between">
                        @if(partnerBranding()?->logo_path)
                            <img src="{{ Storage::url(partnerBranding()->logo_path) }}" 
                                 alt="{{ partnerBranding()->email_sender_name ?? 'Logo' }}" 
                                 class="h-8 w-auto">
                        @else
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ partnerBranding()?->email_sender_name ?? config('app.name', 'DomainDesk') }}
                            </span>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Page Content -->
                <main class="flex-1 overflow-y-auto py-6 pb-20 md:pb-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                    @if(partnerBranding()?->support_email)
                        <p>Support: <a href="mailto:{{ partnerBranding()->support_email }}" class="hover:text-gray-700 dark:hover:text-gray-300">{{ partnerBranding()->support_email }}</a></p>
                    @endif
                    @if(partnerBranding()?->support_phone)
                        <p>Phone: {{ partnerBranding()->support_phone }}</p>
                    @endif
                </div>
            </div>
        </footer>
    </body>
</html>
