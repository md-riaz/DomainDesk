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
    <body class="bg-gray-50 dark:bg-gray-900 antialiased">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex-shrink-0 flex items-center">
                            @if(partnerBranding()?->logo_path)
                                <img src="{{ Storage::url(partnerBranding()->logo_path) }}" 
                                     alt="{{ partnerBranding()->email_sender_name ?? 'Logo' }}" 
                                     class="h-8 w-auto">
                            @else
                                <span class="text-xl font-semibold text-gray-900 dark:text-white">
                                    {{ partnerBranding()?->email_sender_name ?? config('app.name', 'DomainDesk') }}
                                </span>
                            @endif
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <a href="{{ route('client.dashboard') }}" 
                               class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('client.dashboard') ? 'border-blue-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="{{ route('client.domains.search') }}" 
                               class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('client.domains.*') ? 'border-blue-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }} text-sm font-medium">
                                Search Domains
                            </a>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="flex items-center">
                        @auth
                        <div class="ml-3 relative">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ auth()->user()->name }}
                            </span>
                        </div>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-6">
            {{ $slot }}
        </main>

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
