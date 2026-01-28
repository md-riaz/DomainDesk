<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
                    System Health
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Monitor the health and status of system components
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <button wire:click="refresh" type="button"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh Checks
                </button>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="mb-6 rounded-md bg-green-50 dark:bg-green-900 p-4">
                <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        @endif

        <!-- Last Checked -->
        @if($lastChecked)
            <div class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                Last checked: {{ $lastChecked->format('Y-m-d H:i:s') }}
            </div>
        @endif

        <!-- Health Checks -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            @foreach($healthChecks as $name => $check)
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white capitalize">
                            {{ str_replace('_', ' ', $name) }}
                        </h3>
                        
                        @if($check['status'] === 'ok')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Healthy
                            </span>
                        @elseif($check['status'] === 'warning')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Warning
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                Error
                            </span>
                        @endif
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Status:</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $check['message'] }}</span>
                        </div>
                        
                        @if($check['details'])
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Details:</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $check['details'] }}</span>
                            </div>
                        @endif
                    </div>
                    
                    @if($check['troubleshooting'])
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex">
                                <svg class="flex-shrink-0 h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Troubleshooting</h4>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $check['troubleshooting'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- System Information -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">System Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Laravel Version</label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ app()->version() }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">PHP Version</label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ PHP_VERSION }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Server Software</label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Environment</label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ app()->environment() }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Memory Limit</label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ ini_get('memory_limit') }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Execution Time</label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ ini_get('max_execution_time') }}s</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cache Driver</label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ config('cache.default') }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Queue Driver</label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ config('queue.default') }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Session Driver</label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ config('session.driver') }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Database Driver</label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ config('database.default') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
