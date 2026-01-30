<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-white dark:bg-gray-900">
        <div class="min-h-screen flex flex-col">
            <!-- Header -->
            <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    <div class="flex justify-between items-center">
                        <div class="flex-shrink-0">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ config('app.name') }}
                            </h1>
                        </div>
                        @if (Route::has('login'))
                            <nav class="flex items-center gap-4">
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                                        Dashboard
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                                        Log in
                                    </a>

                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                                            Register
                                        </a>
                                    @endif
                                @endauth
                            </nav>
                        @endif
                    </div>
                </div>
            </header>

            <!-- Hero Section -->
            <main class="flex-grow">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <div class="text-center">
                        <h2 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 dark:text-white mb-6">
                            White-Label Domain Reseller Platform
                        </h2>
                        <p class="text-xl text-gray-600 dark:text-gray-300 mb-12 max-w-3xl mx-auto">
                            Empower your business with our comprehensive SaaS platform. Manage partners, clients, and domains with complete white-label branding capabilities.
                        </p>

                        <!-- CTA Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
                            <a href="{{ route('login') }}" class="px-8 py-4 text-lg font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                                Login to Dashboard
                            </a>
                            @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-8 py-4 text-lg font-medium text-blue-600 bg-white border-2 border-blue-600 hover:bg-blue-50 rounded-lg">
                                Get Started
                            </a>
                            @endif
                        </div>

                        <!-- Demo Credentials Section -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-8 max-w-4xl mx-auto mb-16">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                                Try the Demo
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                                    <div class="flex items-center mb-4">
                                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                        </div>
                                        <h4 class="ml-3 text-lg font-semibold text-gray-900 dark:text-white">Super Admin</h4>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <p class="text-gray-600 dark:text-gray-300"><span class="font-medium">Email:</span> admin@domaindesk.com</p>
                                        <p class="text-gray-600 dark:text-gray-300"><span class="font-medium">Password:</span> password</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Full system control, partner management, registrar configuration</p>
                                    </div>
                                </div>

                                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                                    <div class="flex items-center mb-4">
                                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </div>
                                        <h4 class="ml-3 text-lg font-semibold text-gray-900 dark:text-white">Partner</h4>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <p class="text-gray-600 dark:text-gray-300"><span class="font-medium">Email:</span> partner@example.com</p>
                                        <p class="text-gray-600 dark:text-gray-300"><span class="font-medium">Password:</span> password</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Manage clients, custom branding, pricing rules, wallet balance</p>
                                    </div>
                                </div>

                                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                                    <div class="flex items-center mb-4">
                                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                        <h4 class="ml-3 text-lg font-semibold text-gray-900 dark:text-white">Client</h4>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <p class="text-gray-600 dark:text-gray-300"><span class="font-medium">Email:</span> client@example.com</p>
                                        <p class="text-gray-600 dark:text-gray-300"><span class="font-medium">Password:</span> password</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">Search domains, register, manage DNS, view invoices</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="mt-24 grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="text-center p-6">
                            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Multi-Tenant Architecture</h3>
                            <p class="text-gray-600 dark:text-gray-300">Manage multiple partners with complete data isolation. Each partner operates independently with their own branding and clients.</p>
                        </div>

                        <div class="text-center p-6">
                            <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">White-Label Branding</h3>
                            <p class="text-gray-600 dark:text-gray-300">Custom logos, colors, and domain names for each partner. Build your brand identity with complete customization.</p>
                        </div>

                        <div class="text-center p-6">
                            <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Flexible Pricing</h3>
                            <p class="text-gray-600 dark:text-gray-300">Partner-specific pricing rules with markup control. Wallet-based billing with transparent transaction tracking.</p>
                        </div>
                    </div>

                    <!-- How It Works Section -->
                    <div class="mt-24">
                        <h3 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-12">How It Works</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="relative">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-600 text-white font-bold text-xl">
                                            1
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Admin Creates Partners</h4>
                                        <p class="text-gray-600 dark:text-gray-300">Super Admin creates partner accounts, assigns wallet balance, and configures system-wide settings.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="relative">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-600 text-white font-bold text-xl">
                                            2
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Partners Manage Clients</h4>
                                        <p class="text-gray-600 dark:text-gray-300">Partners create client accounts, customize branding, set pricing rules, and manage their business operations.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="relative">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-purple-600 text-white font-bold text-xl">
                                            3
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Clients Use Services</h4>
                                        <p class="text-gray-600 dark:text-gray-300">Clients search and register domains, manage DNS records, handle renewals, and track their invoices.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Key Capabilities -->
                    <div class="mt-24 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-2xl p-8 md:p-12">
                        <h3 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-8">Key Capabilities</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="ml-3 text-gray-700 dark:text-gray-300">Complete partner and client management system</span>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="ml-3 text-gray-700 dark:text-gray-300">White-label branding with custom logos and colors</span>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="ml-3 text-gray-700 dark:text-gray-300">Domain registration and renewal automation</span>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="ml-3 text-gray-700 dark:text-gray-300">Wallet-based billing with transaction history</span>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="ml-3 text-gray-700 dark:text-gray-300">DNS and nameserver management</span>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="ml-3 text-gray-700 dark:text-gray-300">Comprehensive audit logs and reporting</span>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="ml-3 text-gray-700 dark:text-gray-300">Multiple domain registrar integration (ResellerClub, BTCL)</span>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="ml-3 text-gray-700 dark:text-gray-300">Partner-specific pricing rules and markup control</span>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="text-center text-gray-600 dark:text-gray-400">
                        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
