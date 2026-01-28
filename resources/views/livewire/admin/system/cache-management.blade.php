<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl">
                Cache Management
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Manage application caches and optimize performance
            </p>
        </div>

        @if (session()->has('success'))
            <div class="mb-6 rounded-md bg-green-50 dark:bg-green-900 p-4">
                <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 rounded-md bg-red-50 dark:bg-red-900 p-4">
                <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Cache Statistics -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Cache Statistics</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Cache Driver</label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $cacheStats['driver'] }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Available Cache Stores</label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ implode(', ', $cacheStats['stores']) }}</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Quick Actions</h3>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700 last:border-0">
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Clear All Caches</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Clear application, config, route, view, and event caches
                        </p>
                    </div>
                    <button wire:click="confirmClearAll" type="button"
                        class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                        Clear All
                    </button>
                </div>

                <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700 last:border-0">
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Application Cache</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Clear the application cache and settings cache
                        </p>
                    </div>
                    <button wire:click="confirmClearCache('application')" type="button"
                        class="ml-4 inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Clear
                    </button>
                </div>

                <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700 last:border-0">
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Config Cache</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Clear the configuration cache
                        </p>
                    </div>
                    <button wire:click="confirmClearCache('config')" type="button"
                        class="ml-4 inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Clear
                    </button>
                </div>

                <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700 last:border-0">
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Route Cache</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Clear the route cache
                        </p>
                    </div>
                    <button wire:click="confirmClearCache('route')" type="button"
                        class="ml-4 inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Clear
                    </button>
                </div>

                <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700 last:border-0">
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">View Cache</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Clear compiled view files
                        </p>
                    </div>
                    <button wire:click="confirmClearCache('view')" type="button"
                        class="ml-4 inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Clear
                    </button>
                </div>

                <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700 last:border-0">
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Event Cache</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Clear cached event listeners
                        </p>
                    </div>
                    <button wire:click="confirmClearCache('event')" type="button"
                        class="ml-4 inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Optimize Cache -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Optimize Cache</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Cache configuration, routes, views, and events for better performance
                    </p>
                </div>
                <button wire:click="optimizeCache" type="button"
                    class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Optimize
                </button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    @if($showConfirmation)
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" wire:click="cancelClear"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Clear {{ ucfirst($confirmationType) }} Cache?
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Are you sure you want to clear the {{ $confirmationType }} cache? 
                                        @if($confirmationType === 'all')
                                            This will clear all caches and may temporarily slow down the application.
                                        @else
                                            This action cannot be undone.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="executeClear" type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Clear Cache
                        </button>
                        <button wire:click="cancelClear" type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
