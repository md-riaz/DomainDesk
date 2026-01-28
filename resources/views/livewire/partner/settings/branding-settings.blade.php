<div class="px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Branding Settings</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Customize your white-label branding, colors, and contact information.
            </p>
        </div>

        <!-- Messages -->
        @if (session()->has('message'))
            <div class="mb-6 rounded-md bg-green-50 dark:bg-green-900/20 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('message') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <form wire:submit.prevent="save" class="space-y-8">
            <!-- Logo & Favicon Section -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Logo & Favicon</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Logo Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Logo
                            <span class="text-xs text-gray-500 ml-2">(PNG, JPG, SVG - Max 2MB)</span>
                        </label>
                        
                        @if ($logoPath)
                            <div class="mb-4">
                                <img src="{{ Storage::url($logoPath) }}" alt="Current Logo" class="h-16 w-auto border border-gray-300 dark:border-gray-600 rounded p-2 bg-white dark:bg-gray-900">
                                <button type="button" wire:click="removeLogo" class="mt-2 text-sm text-red-600 hover:text-red-800 dark:text-red-400">Remove Logo</button>
                            </div>
                        @endif

                        <div class="mt-2">
                            <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/jpg,image/svg+xml" class="block w-full text-sm text-gray-900 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none">
                            @error('logo') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            
                            @if ($logo)
                                <div wire:loading wire:target="logo" class="mt-2 text-sm text-blue-600 dark:text-blue-400">Uploading...</div>
                            @endif
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Recommended size: 200x50px</p>
                    </div>

                    <!-- Favicon Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Favicon
                            <span class="text-xs text-gray-500 ml-2">(ICO, PNG - Max 100KB)</span>
                        </label>
                        
                        @if ($faviconPath)
                            <div class="mb-4">
                                <img src="{{ Storage::url($faviconPath) }}" alt="Current Favicon" class="h-8 w-8 border border-gray-300 dark:border-gray-600 rounded p-1 bg-white dark:bg-gray-900">
                                <button type="button" wire:click="removeFavicon" class="mt-2 text-sm text-red-600 hover:text-red-800 dark:text-red-400">Remove Favicon</button>
                            </div>
                        @endif

                        <div class="mt-2">
                            <input type="file" wire:model="favicon" accept="image/x-icon,image/png" class="block w-full text-sm text-gray-900 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none">
                            @error('favicon') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            
                            @if ($favicon)
                                <div wire:loading wire:target="favicon" class="mt-2 text-sm text-blue-600 dark:text-blue-400">Uploading...</div>
                            @endif
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Recommended size: 32x32px</p>
                    </div>
                </div>
            </div>

            <!-- Color Scheme Section -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Color Scheme</h2>
                    <button type="button" wire:click="resetColors" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">Reset to Defaults</button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Primary Color -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Primary Color</label>
                        <div class="flex gap-3">
                            <input type="color" wire:model.live="primaryColor" class="h-10 w-20 rounded border-gray-300 dark:border-gray-600">
                            <input type="text" wire:model.live="primaryColor" placeholder="#3B82F6" class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        @error('primaryColor') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        <div class="mt-2 p-3 rounded" style="background-color: {{ $primaryColor }};">
                            <span class="text-white text-sm font-medium">Preview</span>
                        </div>
                    </div>

                    <!-- Secondary Color -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Secondary Color</label>
                        <div class="flex gap-3">
                            <input type="color" wire:model.live="secondaryColor" class="h-10 w-20 rounded border-gray-300 dark:border-gray-600">
                            <input type="text" wire:model.live="secondaryColor" placeholder="#10B981" class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        @error('secondaryColor') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        <div class="mt-2 p-3 rounded" style="background-color: {{ $secondaryColor }};">
                            <span class="text-white text-sm font-medium">Preview</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Settings Section -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Email Settings</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Sender Name *</label>
                        <input type="text" wire:model="emailSenderName" placeholder="Your Company Name" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('emailSenderName') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Sender Address *</label>
                        <input type="email" wire:model="emailSenderEmail" placeholder="noreply@yourdomain.com" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('emailSenderEmail') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reply-To Email</label>
                        <input type="email" wire:model="replyToEmail" placeholder="support@yourdomain.com" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('replyToEmail') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Support Contact Section -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Support Contact</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Support Email *</label>
                        <input type="email" wire:model="supportEmail" placeholder="support@yourdomain.com" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('supportEmail') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Support Phone</label>
                        <input type="text" wire:model="supportPhone" placeholder="+1 (555) 123-4567" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('supportPhone') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Support URL</label>
                        <input type="url" wire:model="supportUrl" placeholder="https://support.yourdomain.com" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('supportUrl') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Preview Toggle -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <button type="button" wire:click="togglePreview" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm font-medium">
                    {{ $showPreview ? 'Hide' : 'Show' }} Live Preview
                </button>

                @if ($showPreview)
                    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Sample Login Page</h3>
                        <div class="border-2 border-gray-300 dark:border-gray-600 rounded-lg p-8" style="background-color: {{ $primaryColor }}20;">
                            <div class="max-w-md mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                                @if ($logoPath || $logo)
                                    <img src="{{ $logo ? $logo->temporaryUrl() : Storage::url($logoPath) }}" alt="Logo" class="h-12 w-auto mx-auto mb-6">
                                @else
                                    <div class="h-12 w-48 mx-auto mb-6 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                                        <span class="text-gray-500 text-sm">Your Logo</span>
                                    </div>
                                @endif
                                <h2 class="text-2xl font-bold text-center mb-4" style="color: {{ $primaryColor }};">Welcome Back</h2>
                                <button type="button" class="w-full py-2 px-4 rounded-md text-white font-medium" style="background-color: {{ $primaryColor }};">
                                    Sign In
                                </button>
                                <p class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                    Need help? Contact <a href="mailto:{{ $supportEmail }}" class="font-medium" style="color: {{ $secondaryColor }};">{{ $supportEmail }}</a>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save Settings</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>
