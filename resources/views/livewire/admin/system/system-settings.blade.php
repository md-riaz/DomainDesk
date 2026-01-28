<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl">
                System Settings
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Configure system-wide settings and preferences
            </p>
        </div>

        @if (session()->has('success'))
            <div class="mb-6 rounded-md bg-green-50 dark:bg-green-900 p-4">
                <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        @endif

        @if (session()->has('info'))
            <div class="mb-6 rounded-md bg-blue-50 dark:bg-blue-900 p-4">
                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ session('info') }}</p>
            </div>
        @endif

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="switchTab('general')" type="button"
                    class="@if($activeTab === 'general') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    General
                </button>
                <button wire:click="switchTab('email')" type="button"
                    class="@if($activeTab === 'email') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Email
                </button>
                <button wire:click="switchTab('domain')" type="button"
                    class="@if($activeTab === 'domain') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Domain
                </button>
                <button wire:click="switchTab('billing')" type="button"
                    class="@if($activeTab === 'billing') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Billing
                </button>
                <button wire:click="switchTab('system')" type="button"
                    class="@if($activeTab === 'system') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    System
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <!-- General Settings -->
            @if($activeTab === 'general')
                <form wire:submit="saveGeneralSettings">
                    <div class="px-6 py-5 space-y-6">
                        <div>
                            <label for="site_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Name</label>
                            <input wire:model="site_name" type="text" id="site_name"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('site_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="admin_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Admin Email</label>
                            <input wire:model="admin_email" type="email" id="admin_email"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('admin_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="default_timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Timezone</label>
                            <select wire:model="default_timezone" id="default_timezone"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @foreach($timezones as $tz)
                                    <option value="{{ $tz }}">{{ $tz }}</option>
                                @endforeach
                            </select>
                            @error('default_timezone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="default_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Currency</label>
                            <select wire:model="default_currency" id="default_currency"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency }}">{{ $currency }}</option>
                                @endforeach
                            </select>
                            @error('default_currency') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="date_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date Format</label>
                                <input wire:model="date_format" type="text" id="date_format"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    placeholder="Y-m-d">
                                @error('date_format') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="time_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Time Format</label>
                                <input wire:model="time_format" type="text" id="time_format"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    placeholder="H:i:s">
                                @error('time_format') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex justify-between">
                        <button wire:click.prevent="resetToDefaults" type="button"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Reset to Defaults
                        </button>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Save Settings
                        </button>
                    </div>
                </form>
            @endif

            <!-- Email Settings -->
            @if($activeTab === 'email')
                <form wire:submit="saveEmailSettings">
                    <div class="px-6 py-5 space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="smtp_host" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Host</label>
                                <input wire:model="smtp_host" type="text" id="smtp_host"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('smtp_host') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="smtp_port" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Port</label>
                                <input wire:model="smtp_port" type="number" id="smtp_port"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('smtp_port') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="smtp_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Username</label>
                            <input wire:model="smtp_username" type="text" id="smtp_username"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('smtp_username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="smtp_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Password</label>
                            <input wire:model="smtp_password" type="password" id="smtp_password"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="Leave empty to keep current">
                            @error('smtp_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="smtp_encryption" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Encryption</label>
                            <select wire:model="smtp_encryption" id="smtp_encryption"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                            </select>
                            @error('smtp_encryption') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="mail_from_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Address</label>
                            <input wire:model="mail_from_address" type="email" id="mail_from_address"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('mail_from_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="mail_from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Name</label>
                            <input wire:model="mail_from_name" type="text" id="mail_from_name"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('mail_from_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex justify-between">
                        <div class="space-x-3">
                            <button wire:click.prevent="resetToDefaults" type="button"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Reset to Defaults
                            </button>
                            <button wire:click.prevent="testEmail" type="button"
                                class="inline-flex items-center px-4 py-2 border border-blue-300 dark:border-blue-600 rounded-md shadow-sm text-sm font-medium text-blue-700 dark:text-blue-300 bg-white dark:bg-gray-700 hover:bg-blue-50 dark:hover:bg-gray-600">
                                Test Email
                            </button>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Save Settings
                        </button>
                    </div>
                </form>
            @endif

            <!-- Domain Settings -->
            @if($activeTab === 'domain')
                <form wire:submit="saveDomainSettings">
                    <div class="px-6 py-5 space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="default_nameserver_1" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nameserver 1 *</label>
                                <input wire:model="default_nameserver_1" type="text" id="default_nameserver_1"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('default_nameserver_1') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="default_nameserver_2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nameserver 2 *</label>
                                <input wire:model="default_nameserver_2" type="text" id="default_nameserver_2"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('default_nameserver_2') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="default_nameserver_3" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nameserver 3</label>
                                <input wire:model="default_nameserver_3" type="text" id="default_nameserver_3"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('default_nameserver_3') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="default_nameserver_4" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nameserver 4</label>
                                <input wire:model="default_nameserver_4" type="text" id="default_nameserver_4"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('default_nameserver_4') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="default_ttl" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default TTL (seconds)</label>
                            <input wire:model="default_ttl" type="number" id="default_ttl"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Default: 86400 (24 hours)</p>
                            @error('default_ttl') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="auto_renewal_lead_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Auto-Renewal Lead Time (days)</label>
                            <input wire:model="auto_renewal_lead_time" type="number" id="auto_renewal_lead_time"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Days before expiration to trigger auto-renewal</p>
                            @error('auto_renewal_lead_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="grace_period_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Grace Period (days)</label>
                            <input wire:model="grace_period_days" type="number" id="grace_period_days"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Days after expiration before domain is deleted</p>
                            @error('grace_period_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex justify-between">
                        <button wire:click.prevent="resetToDefaults" type="button"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Reset to Defaults
                        </button>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Save Settings
                        </button>
                    </div>
                </form>
            @endif

            <!-- Billing Settings -->
            @if($activeTab === 'billing')
                <form wire:submit="saveBillingSettings">
                    <div class="px-6 py-5 space-y-6">
                        <div>
                            <label for="currency_symbol" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Currency Symbol</label>
                            <input wire:model="currency_symbol" type="text" id="currency_symbol"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('currency_symbol') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="tax_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tax Rate (%)</label>
                            <input wire:model="tax_rate" type="number" step="0.01" id="tax_rate"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('tax_rate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="invoice_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice Number Prefix</label>
                            <input wire:model="invoice_prefix" type="text" id="invoice_prefix"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('invoice_prefix') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="low_balance_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Low Balance Threshold</label>
                            <input wire:model="low_balance_threshold" type="number" step="0.01" id="low_balance_threshold"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Alert partners when balance drops below this amount</p>
                            @error('low_balance_threshold') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex justify-between">
                        <button wire:click.prevent="resetToDefaults" type="button"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Reset to Defaults
                        </button>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Save Settings
                        </button>
                    </div>
                </form>
            @endif

            <!-- System Settings (Read-only Info) -->
            @if($activeTab === 'system')
                <div class="px-6 py-5 space-y-6">
                    <div class="bg-yellow-50 dark:bg-yellow-900 rounded-md p-4">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            These settings are read-only and configured via environment variables or config files.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Maintenance Mode</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $maintenance_mode ? 'Enabled' : 'Disabled' }}
                                <a href="{{ route('admin.system.maintenance') }}" class="ml-2 text-blue-600 hover:text-blue-800">
                                    Configure →
                                </a>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Debug Mode</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $debug_mode ? 'Enabled' : 'Disabled' }}
                                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">(Configure in .env)</span>
                            </p>
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
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Laravel Version</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ app()->version() }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">PHP Version</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ PHP_VERSION }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
