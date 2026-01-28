<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl">
                Maintenance Mode
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Enable or disable maintenance mode for the entire application
            </p>
        </div>

        @if (session()->has('success'))
            <div class="mb-6 rounded-md bg-green-50 dark:bg-green-900 p-4">
                <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        @endif

        @if (session()->has('warning'))
            <div class="mb-6 rounded-md bg-yellow-50 dark:bg-yellow-900 p-4">
                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">{{ session('warning') }}</p>
            </div>
        @endif

        <!-- Status Card -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Current Status</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Maintenance mode is currently 
                        <span class="font-semibold {{ $isMaintenanceMode ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $isMaintenanceMode ? 'ENABLED' : 'DISABLED' }}
                        </span>
                    </p>
                </div>
                <div>
                    @if($isMaintenanceMode)
                        <span class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Maintenance Active
                        </span>
                    @else
                        <span class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Site Active
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Configuration -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Configuration</h3>
            
            <form wire:submit="saveMessage">
                <div class="space-y-6">
                    <div>
                        <label for="maintenanceMessage" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Maintenance Message
                        </label>
                        <textarea wire:model="maintenanceMessage" id="maintenanceMessage" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            placeholder="We are performing scheduled maintenance. Please check back soon."></textarea>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            This message will be displayed to visitors during maintenance mode.
                        </p>
                        @error('maintenanceMessage') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="allowedIps" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Allowed IP Addresses (Optional)
                        </label>
                        <input wire:model="allowedIps" type="text" id="allowedIps"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            placeholder="192.168.1.1, 10.0.0.1">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Comma-separated list of IP addresses that can access the site during maintenance.
                        </p>
                        @error('allowedIps') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex space-x-3">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Save Configuration
                        </button>
                        <button wire:click.prevent="togglePreview" type="button"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            {{ $showPreview ? 'Hide' : 'Show' }} Preview
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Preview -->
        @if($showPreview)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Preview</h3>
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h2 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Site Under Maintenance</h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">{{ $maintenanceMessage }}</p>
                </div>
            </div>
        @endif

        <!-- Toggle Maintenance Mode -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-start">
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ $isMaintenanceMode ? 'Disable' : 'Enable' }} Maintenance Mode
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if($isMaintenanceMode)
                            Click to disable maintenance mode and make the site available to all visitors.
                        @else
                            Click to enable maintenance mode. The site will be unavailable to visitors except via a secret URL.
                        @endif
                    </p>
                    
                    @if(!$isMaintenanceMode)
                        <div class="mt-3 bg-yellow-50 dark:bg-yellow-900 rounded-md p-3">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                <strong>Warning:</strong> Enabling maintenance mode will make the site unavailable to regular users. 
                                You will still have access via a secret URL.
                            </p>
                        </div>
                    @endif
                </div>
                
                <button wire:click="toggleMaintenanceMode" type="button"
                    class="ml-6 inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-sm font-medium text-white 
                    {{ $isMaintenanceMode ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }}">
                    {{ $isMaintenanceMode ? 'Disable Maintenance Mode' : 'Enable Maintenance Mode' }}
                </button>
            </div>
        </div>
    </div>
</div>
