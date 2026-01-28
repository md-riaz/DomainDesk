<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6">
            <h1 class="text-3xl font-bold text-white">Renew Domain</h1>
            <p class="text-indigo-100 mt-2">Extend the registration period for {{ $domain->name }}</p>
        </div>

        <!-- Domain Information -->
        <div class="px-8 py-6 bg-gray-50 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Domain Name</label>
                    <p class="text-lg font-semibold text-gray-900">{{ $domain->name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Expiry Date</label>
                    <p class="text-lg font-semibold 
                        {{ $domain->daysUntilExpiry() <= 7 ? 'text-red-600' : ($domain->daysUntilExpiry() <= 30 ? 'text-yellow-600' : 'text-gray-900') }}">
                        {{ $domain->expires_at->format('M j, Y') }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        @if ($domain->daysUntilExpiry() > 0)
                            {{ $domain->daysUntilExpiry() }} days remaining
                        @else
                            Expired {{ abs($domain->daysUntilExpiry()) }} days ago
                        @endif
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <p class="text-lg">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $domain->status->value === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $domain->status->label() }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Renewability Check -->
        @if ($renewabilityCheck && !$renewabilityCheck['renewable'])
            <div class="mx-8 mt-6 bg-red-50 border-l-4 border-red-500 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-800">Domain Cannot Be Renewed</h3>
                        <p class="text-sm text-red-700 mt-1">{{ $renewabilityCheck['reason'] }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Grace Period Warning -->
        @if ($renewabilityCheck && $renewabilityCheck['in_grace_period'])
            <div class="mx-8 mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-yellow-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-yellow-800">Grace Period</h3>
                        <p class="text-sm text-yellow-700 mt-1">This domain is in the grace period. A 20% surcharge will be applied to the renewal cost.</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Error Message -->
        @if ($errorMessage)
            <div class="mx-8 mt-6 bg-red-50 border-l-4 border-red-500 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-red-700">{{ $errorMessage }}</p>
                </div>
            </div>
        @endif

        <!-- Success Message -->
        @if ($successMessage)
            <div class="mx-8 mt-6 bg-green-50 border-l-4 border-green-500 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-green-700">{{ $successMessage }}</p>
                </div>
            </div>
        @endif

        <!-- Renewal Form -->
        @if ($renewabilityCheck && $renewabilityCheck['renewable'])
            <div class="px-8 py-6">
                <form wire:submit="renewDomain">
                    <!-- Renewal Period Selector -->
                    <div class="mb-6">
                        <label for="years" class="block text-sm font-medium text-gray-700 mb-2">
                            Renewal Period
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                            @for ($i = 1; $i <= 10; $i++)
                                <button type="button" wire:click="$set('years', {{ $i }})" 
                                    class="px-4 py-3 border rounded-lg text-center transition-colors
                                        {{ $years === $i ? 'border-indigo-600 bg-indigo-50 text-indigo-700 font-semibold' : 'border-gray-300 hover:border-indigo-300' }}">
                                    {{ $i }} {{ $i === 1 ? 'Year' : 'Years' }}
                                </button>
                            @endfor
                        </div>
                    </div>

                    <!-- Price Display -->
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Renewal Cost</h3>
                                <p class="text-sm text-gray-500">For {{ $years }} {{ $years === 1 ? 'year' : 'years' }}</p>
                            </div>
                            <div class="text-right">
                                @if ($price)
                                    <p class="text-3xl font-bold text-gray-900">${{ number_format($price, 2) }}</p>
                                @else
                                    <p class="text-gray-500">Calculating...</p>
                                @endif
                            </div>
                        </div>

                        <!-- Wallet Balance -->
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Your Wallet Balance:</span>
                                <span class="text-sm font-semibold {{ $walletBalance >= ($price ?? 0) ? 'text-green-600' : 'text-red-600' }}">
                                    ${{ number_format($walletBalance, 2) }}
                                </span>
                            </div>
                            @if ($walletBalance < ($price ?? 0))
                                <p class="text-xs text-red-600 mt-2">
                                    Insufficient balance. You need ${{ number_format(($price ?? 0) - $walletBalance, 2) }} more to complete this renewal.
                                </p>
                            @endif
                        </div>

                        <!-- New Expiry Date -->
                        @if ($domain->expires_at)
                            <div class="border-t border-gray-200 pt-4 mt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">New Expiry Date:</span>
                                    <span class="text-sm font-semibold text-gray-900">
                                        {{ $domain->expires_at->copy()->addYears($years)->format('M j, Y') }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between">
                        <a href="{{ route('client.dashboard') }}" 
                            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        
                        <button type="submit" 
                            wire:loading.attr="disabled"
                            {{ $walletBalance < ($price ?? 0) ? 'disabled' : '' }}
                            class="px-8 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 
                                transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                            <span wire:loading.remove>Renew Domain</span>
                            <span wire:loading>
                                <svg class="animate-spin h-5 w-5 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
