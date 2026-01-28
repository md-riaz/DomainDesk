<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Transfer Domain</h2>

                @if ($errorMessage)
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                        {{ $errorMessage }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-6">
                    <h3 class="font-semibold text-blue-900 mb-2">Transfer Process Information</h3>
                    <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                        <li>Domain transfers typically take 5-7 days to complete</li>
                        <li>Your domain must be unlocked at the current registrar</li>
                        <li>Domain must be at least 60 days old (ICANN requirement)</li>
                        <li>Transfer includes 1 year renewal added to current expiry date</li>
                        <li>You can cancel the transfer within 5 days if needed</li>
                    </ul>
                </div>

                <form wire:submit="transfer" class="space-y-6">
                    {{-- Domain Name --}}
                    <div>
                        <label for="domainName" class="block text-sm font-medium text-gray-700 mb-2">
                            Domain Name
                        </label>
                        <input
                            type="text"
                            id="domainName"
                            wire:model.blur="domainName"
                            placeholder="example.com"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :disabled="$isProcessing"
                        >
                        @error('domainName')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Auth Code --}}
                    <div>
                        <label for="authCode" class="block text-sm font-medium text-gray-700 mb-2">
                            Authorization Code (EPP/Auth Code)
                        </label>
                        <div class="relative">
                            <input
                                type="{{ $showAuthCode ? 'text' : 'password' }}"
                                id="authCode"
                                wire:model="authCode"
                                placeholder="Enter authorization code"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-12"
                                :disabled="$isProcessing"
                            >
                            <button
                                type="button"
                                wire:click="toggleAuthCodeVisibility"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
                            >
                                @if ($showAuthCode)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                @endif
                            </button>
                        </div>
                        @error('authCode')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            You can obtain this from your current domain registrar
                        </p>
                    </div>

                    {{-- Auto Renew --}}
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="autoRenew"
                            wire:model="autoRenew"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            :disabled="$isProcessing"
                        >
                        <label for="autoRenew" class="ml-2 text-sm text-gray-700">
                            Enable automatic renewal
                        </label>
                    </div>

                    {{-- Pricing Summary --}}
                    @if ($transferFee !== null)
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Transfer Summary</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Domain Transfer (includes 1 year renewal)</span>
                                    <span class="font-medium text-gray-900">${{ number_format($transferFee, 2) }}</span>
                                </div>
                                <div class="border-t border-gray-300 pt-2 mt-2">
                                    <div class="flex justify-between font-bold">
                                        <span class="text-gray-900">Total</span>
                                        <span class="text-gray-900">${{ number_format($transferFee, 2) }}</span>
                                    </div>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600">Current Wallet Balance</span>
                                    <span class="font-medium {{ $walletBalance >= $transferFee ? 'text-green-600' : 'text-red-600' }}">
                                        ${{ number_format($walletBalance, 2) }}
                                    </span>
                                </div>
                            </div>

                            @if ($walletBalance < $transferFee)
                                <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
                                    Insufficient balance. Please add funds to your wallet.
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Submit Button --}}
                    <div class="flex gap-4">
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="transfer"
                            :disabled="$isProcessing || !$transferFee || $walletBalance < $transferFee"
                            class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            <span wire:loading.remove wire:target="transfer">Initiate Transfer</span>
                            <span wire:loading wire:target="transfer">Processing...</span>
                        </button>

                        <a
                            href="{{ route('client.domains.search') }}"
                            class="px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition text-center"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
