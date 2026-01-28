<div>
    <div class="mb-6 flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
        <div class="flex flex-col lg:flex-row lg:items-center lg:space-x-4 space-y-4 lg:space-y-0">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search TLDs..."
                class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
            
            <select wire:model.live="registrarFilter" class="rounded-lg border-gray-300 shadow-sm">
                <option value="all">All Registrars</option>
                @foreach($registrars as $registrar)
                    <option value="{{ $registrar->id }}">{{ $registrar->name }}</option>
                @endforeach
            </select>
            
            <select wire:model.live="statusFilter" class="rounded-lg border-gray-300 shadow-sm">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            
            <select wire:model.live="featureFilter" class="rounded-lg border-gray-300 shadow-sm">
                <option value="all">All Features</option>
                <option value="dns">DNS Support</option>
                <option value="whois_privacy">WHOIS Privacy</option>
            </select>
        </div>
    </div>

    @if(count($selectedTlds) > 0)
        <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-blue-900">
                    {{ count($selectedTlds) }} TLD(s) selected
                </span>
                <div class="flex items-center space-x-2">
                    <button
                        wire:click="bulkActivate"
                        class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-lg text-xs text-white hover:bg-green-700"
                    >
                        Activate
                    </button>
                    <button
                        wire:click="bulkDeactivate"
                        class="inline-flex items-center px-3 py-1.5 bg-gray-600 border border-transparent rounded-lg text-xs text-white hover:bg-gray-700"
                    >
                        Deactivate
                    </button>
                    <select
                        wire:model.live="registrarFilter"
                        wire:change="bulkAssignRegistrar($event.target.value)"
                        class="rounded-lg border-gray-300 text-xs"
                    >
                        <option value="">Assign to Registrar...</option>
                        @foreach($registrars as $registrar)
                            <option value="{{ $registrar->id }}">{{ $registrar->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input
                            type="checkbox"
                            wire:model.live="selectAll"
                            wire:click="toggleSelectAll"
                            class="rounded border-gray-300 text-blue-600"
                        />
                    </th>
                    <th wire:click="updateSortBy('extension')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        <div class="flex items-center">
                            Extension
                            @if($sortBy === 'extension')
                                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="{{ $sortDirection === 'asc' ? 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' : 'M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z' }}" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Registrar
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Base Price
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Features
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($tlds as $tld)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input
                                type="checkbox"
                                wire:model.live="selectedTlds"
                                value="{{ $tld->id }}"
                                class="rounded border-gray-300 text-blue-600"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">.{{ $tld->extension }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $tld->registrar?->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($tld->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @php
                                $basePrice = $tld->getBasePrice('register', 1);
                            @endphp
                            @if($basePrice)
                                ${{ number_format($basePrice, 2) }}/yr
                            @else
                                <span class="text-gray-400">Not set</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                @if($tld->supports_dns)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800" title="DNS Support">
                                        DNS
                                    </span>
                                @endif
                                @if($tld->supports_whois_privacy)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800" title="WHOIS Privacy">
                                        Privacy
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <a
                                href="{{ route('admin.tlds.pricing', $tld->id) }}"
                                class="text-blue-600 hover:text-blue-900"
                            >
                                Pricing
                            </a>
                            <button
                                wire:click="toggleActive({{ $tld->id }})"
                                class="text-blue-600 hover:text-blue-900"
                            >
                                {{ $tld->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                            No TLDs found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $tlds->links() }}
    </div>
</div>

@script
<script>
    $wire.on('tld-updated', (event) => {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { type: 'success', message: event.message }
        }));
    });

    $wire.on('tld-error', (event) => {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { type: 'error', message: event.message }
        }));
    });
</script>
@endscript
