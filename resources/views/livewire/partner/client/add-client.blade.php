<div class="px-4 sm:px-6 lg:px-8 max-w-2xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center">
            <a href="{{ route('partner.clients.list') }}" class="mr-4 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Add New Client</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Create a new client account</p>
            </div>
        </div>
    </div>

    @if($showSuccess)
        <div class="mb-6 rounded-md bg-green-50 dark:bg-green-900/20 p-6 border-2 border-green-200 dark:border-green-800">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-green-800 dark:text-green-300">
                        Client created successfully!
                    </h3>
                    <div class="mt-2 text-sm text-green-700 dark:text-green-400">
                        <p><strong>Email:</strong> {{ $email }}</p>
                        <p><strong>Password:</strong> {{ $generatedPassword }}</p>
                        <p class="mt-2 text-xs">Please save these credentials. The password will not be shown again.</p>
                    </div>
                    <div class="mt-4">
                        <button wire:click="resetForm" class="text-sm font-medium text-green-800 dark:text-green-300 hover:text-green-700 dark:hover:text-green-200 underline">
                            Add another client
                        </button>
                        <span class="mx-2 text-green-800 dark:text-green-300">or</span>
                        <a href="{{ route('partner.clients.list') }}" class="text-sm font-medium text-green-800 dark:text-green-300 hover:text-green-700 dark:hover:text-green-200 underline">
                            View all clients
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <form wire:submit="save">
                <div class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="name" id="name" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" wire:model="email" id="email" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Password <span class="text-red-500">*</span>
                            </label>
                            <button type="button" wire:click="generatePassword" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                Generate New
                            </button>
                        </div>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" wire:model="password" id="password" required readonly
                                class="flex-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('password') border-red-500 @enderror">
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Minimum 8 characters with letters and numbers
                        </p>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Send Welcome Email -->
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="sendWelcomeEmail" id="sendWelcomeEmail"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                        <label for="sendWelcomeEmail" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                            Send welcome email with credentials
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('partner.clients.list') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Client
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>
