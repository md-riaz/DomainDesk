<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                {{-- Header --}}
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Transfer Status</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ $domain->name }}</p>
                    </div>
                    <a
                        href="{{ route('client.domains.show', ['domain' => $domain->id]) }}"
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                    >
                        ← Back to Domain
                    </a>
                </div>

                @if ($statusMessage)
                    <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded">
                        {{ $statusMessage }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Status Badge --}}
                <div class="mb-6">
                    <div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold
                        {{ $domain->status->value === 'pending_transfer' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $domain->status->value === 'transfer_in_progress' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $domain->status->value === 'transfer_approved' ? 'bg-purple-100 text-purple-800' : '' }}
                        {{ $domain->status->value === 'transfer_completed' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $domain->status->value === 'transfer_failed' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $domain->status->value === 'transfer_cancelled' ? 'bg-gray-100 text-gray-800' : '' }}
                    ">
                        {{ $domain->status->label() }}
                    </div>
                </div>

                {{-- Progress Bar --}}
                @if ($domain->isTransferring())
                    <div class="mb-8">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Transfer Progress</span>
                            <span class="text-sm font-medium text-gray-700">{{ $progressPercentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div
                                class="bg-blue-600 h-3 rounded-full transition-all duration-500"
                                style="width: {{ $progressPercentage }}%"
                            ></div>
                        </div>
                    </div>
                @endif

                {{-- Transfer Details --}}
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-3">Transfer Details</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Domain Name</dt>
                            <dd class="font-medium text-gray-900">{{ $domain->name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Initiated</dt>
                            <dd class="font-medium text-gray-900">
                                {{ $domain->transfer_initiated_at?->format('M d, Y g:i A') ?? 'N/A' }}
                            </dd>
                        </div>
                        @if ($domain->transfer_completed_at)
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Completed</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ $domain->transfer_completed_at->format('M d, Y g:i A') }}
                                </dd>
                            </div>
                        @endif
                        @if ($domain->isTransferring() && isset($domain->transfer_metadata['estimated_completion']))
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Estimated Completion</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ $domain->transfer_metadata['estimated_completion'] }}
                                </dd>
                            </div>
                        @endif
                        @if ($domain->expires_at)
                            <div class="flex justify-between">
                                <dt class="text-gray-600">New Expiry Date</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ $domain->expires_at->format('M d, Y') }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Status History --}}
                @if (!empty($statusHistory))
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Transfer Timeline</h3>
                        <div class="space-y-3">
                            @foreach ($statusHistory as $item)
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-blue-600"></div>
                                    <div class="ml-4 flex-1">
                                        <div class="flex justify-between">
                                            <p class="text-sm font-medium text-gray-900">{{ $item['status'] }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $item['timestamp']->diffForHumans() }}
                                            </p>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-600">{{ $item['message'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Status Message --}}
                @if ($domain->transfer_status_message)
                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-6">
                        <h4 class="font-semibold text-blue-900 text-sm mb-1">Latest Update</h4>
                        <p class="text-sm text-blue-800">{{ $domain->transfer_status_message }}</p>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex gap-4">
                    @if ($domain->isTransferring())
                        <button
                            wire:click="refreshStatus"
                            wire:loading.attr="disabled"
                            wire:target="refreshStatus"
                            class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 transition"
                        >
                            <span wire:loading.remove wire:target="refreshStatus">Check Status</span>
                            <span wire:loading wire:target="refreshStatus">Checking...</span>
                        </button>
                    @endif

                    @if ($domain->canCancelTransfer())
                        <button
                            wire:click="cancelTransfer"
                            wire:confirm="Are you sure you want to cancel this transfer? Your wallet will be refunded."
                            class="px-6 py-3 border border-red-300 rounded-lg font-medium text-red-700 hover:bg-red-50 transition"
                        >
                            Cancel Transfer
                        </button>
                    @endif
                </div>

                {{-- Info Box --}}
                @if ($domain->isTransferring())
                    <div class="mt-6 bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
                        <h4 class="font-semibold text-yellow-900 text-sm mb-2">What happens next?</h4>
                        <ul class="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                            <li>The current registrar will process the transfer request</li>
                            <li>You may receive an email from the current registrar</li>
                            <li>The transfer typically completes within 5-7 days</li>
                            <li>You can check the status at any time by clicking "Check Status"</li>
                            @if ($domain->canCancelTransfer())
                                <li>You can cancel the transfer within 5 days of initiation</li>
                            @endif
                        </ul>
                    </div>
                @endif

                @if ($domain->status->value === 'transfer_completed')
                    <div class="mt-6 bg-green-50 border border-green-200 p-4 rounded-lg">
                        <h4 class="font-semibold text-green-900 text-sm mb-2">Transfer Completed! 🎉</h4>
                        <p class="text-sm text-green-800">
                            Your domain has been successfully transferred. You can now manage it from your domain dashboard.
                        </p>
                    </div>
                @endif

                @if ($domain->status->value === 'transfer_failed')
                    <div class="mt-6 bg-red-50 border border-red-200 p-4 rounded-lg">
                        <h4 class="font-semibold text-red-900 text-sm mb-2">Transfer Failed</h4>
                        <p class="text-sm text-red-800 mb-2">
                            Unfortunately, the transfer could not be completed. Your wallet has been refunded.
                        </p>
                        <p class="text-sm text-red-800">
                            Please verify the authorization code and domain status at your current registrar, then try again.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
