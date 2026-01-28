<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Domain Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex-1">
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $domain->name }}</h1>
                    @if($domain->status->value === 'active')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                        {{ $domain->status->label() }}
                    </span>
                    @elseif($domain->status->value === 'expired')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                        {{ $domain->status->label() }}
                    </span>
                    @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                        {{ $domain->status->label() }}
                    </span>
                    @endif
                </div>
                @if($domain->expires_at)
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Expires on {{ $domain->expires_at->format('F d, Y') }}
                    @php $daysUntil = $domain->daysUntilExpiry(); @endphp
                    @if($daysUntil !== null)
                        <span class="{{ $daysUntil < 30 ? 'text-orange-600 dark:text-orange-400 font-semibold' : '' }}">
                            ({{ $daysUntil >= 0 ? $daysUntil . ' days remaining' : abs($daysUntil) . ' days overdue' }})
                        </span>
                    @endif
                </p>
                @endif
            </div>
            <div class="mt-4 md:mt-0 flex space-x-3">
                @if($domain->status->isRenewable())
                <a href="{{ route('client.domains.renew', $domain) }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Renew Domain
                </a>
                @endif
                <button wire:click="syncWithRegistrar" 
                        wire:loading.attr="disabled"
                        wire:target="syncWithRegistrar"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50">
                    <svg wire:loading.remove wire:target="syncWithRegistrar" class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <svg wire:loading wire:target="syncWithRegistrar" class="animate-spin mr-2 -ml-1 h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Sync with Registrar
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex" aria-label="Tabs">
                <button wire:click="switchTab('overview')" 
                        class="@if($activeTab === 'overview') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 @endif whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                    Overview
                </button>
                <button wire:click="switchTab('nameservers')" 
                        class="@if($activeTab === 'nameservers') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 @endif whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                    Nameservers
                </button>
                <button wire:click="switchTab('dns')" 
                        class="@if($activeTab === 'dns') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 @endif whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                    DNS Records
                </button>
                <button wire:click="switchTab('contacts')" 
                        class="@if($activeTab === 'contacts') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 @endif whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                    Contacts
                </button>
                <button wire:click="switchTab('documents')" 
                        class="@if($activeTab === 'documents') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 @endif whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                    Documents
                </button>
            </nav>
        </div>

        <div class="p-6">
            @if($activeTab === 'overview')
                <!-- Overview Tab -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Domain Information</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Domain Name</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $domain->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $domain->status->label() }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Registered On</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $domain->registered_at?->format('F d, Y') ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Expires On</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $domain->expires_at?->format('F d, Y') ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Auto-Renew</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $domain->auto_renew ? 'Enabled' : 'Disabled' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Synced</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $domain->last_synced_at?->diffForHumans() ?? 'Never' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Activity</h3>
                        @if(count($activityLog) > 0)
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                @foreach($activityLog as $index => $activity)
                                <li>
                                    <div class="relative pb-8">
                                        @if($index < count($activityLog) - 1)
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-900 dark:text-white">{{ $activity['description'] }}</p>
                                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">by {{ $activity['user_name'] }}</p>
                                                </div>
                                                <div class="text-right text-xs whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                    {{ $activity['created_at']->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No activity recorded yet.</p>
                        @endif
                    </div>
                </div>
            @elseif($activeTab === 'nameservers')
                <!-- Nameservers Tab -->
                @livewire('client.domain.manage-nameservers', ['domain' => $domain], key('nameservers-' . $domain->id))
            @elseif($activeTab === 'dns')
                <!-- DNS Records Tab -->
                @livewire('client.domain.manage-dns', ['domain' => $domain], key('dns-' . $domain->id))
            @elseif($activeTab === 'contacts')
                <!-- Contacts Tab -->
                <div class="space-y-6">
                    @foreach($domain->contacts as $contact)
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">{{ $contact->type->label() }}</h4>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Name</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $contact->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $contact->email }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Organization</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $contact->organization ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Phone</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $contact->phone ?? 'N/A' }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-gray-500 dark:text-gray-400">Address</dt>
                                <dd class="text-gray-900 dark:text-white">
                                    {{ $contact->address }}<br>
                                    {{ $contact->city }}, {{ $contact->state }} {{ $contact->postal_code }}<br>
                                    {{ $contact->country }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                    @endforeach
                    @if($domain->contacts->count() === 0)
                    <p class="text-center text-gray-500 dark:text-gray-400 py-8">No contact information available.</p>
                    @endif
                </div>
            @elseif($activeTab === 'documents')
                <!-- Documents Tab -->
                <div class="space-y-4">
                    @if($domain->documents->count() > 0)
                        @foreach($domain->documents as $document)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <div class="flex items-center">
                                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $document->type->label() }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Uploaded {{ $document->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                            <a href="{{ route('client.domains.documents.download', $document) }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                Download
                            </a>
                        </div>
                        @endforeach
                    @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No documents</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No documents have been uploaded for this domain.</p>
                    </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
