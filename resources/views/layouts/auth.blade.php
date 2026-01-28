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
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div class="w-full sm:max-w-md px-6 py-4">
                <!-- Logo -->
                <div class="flex justify-center mb-6">
                    @if(partnerBranding()?->logo_path)
                        <img src="{{ Storage::url(partnerBranding()->logo_path) }}" 
                             alt="{{ partnerBranding()->email_sender_name ?? 'Logo' }}" 
                             class="h-12 w-auto">
                    @else
                        <a href="/" class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ partnerBranding()?->email_sender_name ?? config('app.name', 'DomainDesk') }}
                        </a>
                    @endif
                </div>

                <!-- Content -->
                <div class="bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
