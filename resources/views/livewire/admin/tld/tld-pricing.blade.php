<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">.{{ $tld->extension }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                Registrar: {{ $tld->registrar?->name ?? 'Not assigned' }}
                @if($tld->registrar?->last_sync_at)
                    · Last synced: {{ $tld->registrar->last_sync_at->diffForHumans() }}
                @endif
            </p>
        </div>
        
        <div class="flex items-center space-x-3">
            <button
                wire:click="$toggle('showManualOverride')"
                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
            >
                Manual Override
            </button>
            
            @if($tld->registrar)
                <button
                    wire:click="syncPrices"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="syncPrices">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Sync from Registrar
                    </span>
                    <span wire:loading wire:target="syncPrices">Syncing...</span>
                </button>
            @endif
        </div>
    </div>

    @if($syncResult)
        <div class="mb-6 rounded-lg p-4 {{ $syncResult === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
            <div class="flex items-center">
                @if($syncResult === 'success')
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-green-800 font-medium">{{ $syncMessage }}</span>
                @else
                    <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-800 font-medium">{{ $syncMessage }}</span>
                @endif
            </div>
        </div>
    @endif

    @if($showManualOverride)
        <div class="mb-6 bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Manual Price Override</h3>
            
            <form wire:submit="saveManualPrice" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="selectedAction" class="block text-sm font-medium text-gray-700">Action</label>
                        <select
                            wire:model="selectedAction"
                            id="selectedAction"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            @foreach($actions as $action)
                                <option value="{{ $action->value }}">{{ $action->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label for="selectedYears" class="block text-sm font-medium text-gray-700">Years</label>
                        <select
                            wire:model="selectedYears"
                            id="selectedYears"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            @for($i = $tld->min_years; $i <= $tld->max_years; $i++)
                                <option value="{{ $i }}">{{ $i }} {{ Str::plural('year', $i) }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="newPrice" class="block text-sm font-medium text-gray-700">Price (USD)</label>
                        <input
                            type="number"
                            wire:model="newPrice"
                            id="newPrice"
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            required
                        />
                        @error('newPrice') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label for="effectiveDate" class="block text-sm font-medium text-gray-700">Effective Date</label>
                        <input
                            type="date"
                            wire:model="effectiveDate"
                            id="effectiveDate"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            required
                        />
                        @error('effectiveDate') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                    <textarea
                        wire:model="notes"
                        id="notes"
                        rows="2"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Reason for manual override..."
                    ></textarea>
                </div>
                
                @error('general')
                    <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                        <span class="text-red-800">{{ $message }}</span>
                    </div>
                @enderror
                
                <div class="flex items-center justify-end space-x-3">
                    <button
                        type="button"
                        wire:click="$set('showManualOverride', false)"
                        class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                    >
                        Save Price
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Current Pricing</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Action
                        </th>
                        @for($years = $tld->min_years; $years <= $tld->max_years; $years++)
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $years }} {{ Str::plural('Year', $years) }}
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($actions as $action)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $action->label() }}
                            </td>
                            @for($years = $tld->min_years; $years <= $tld->max_years; $years++)
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if(isset($prices[$action->value][$years]))
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-900">
                                                ${{ number_format($prices[$action->value][$years]['price'], 2) }}
                                            </span>
                                            <button
                                                wire:click="toggleHistory('{{ $action->value }}', {{ $years }})"
                                                class="ml-2 text-blue-600 hover:text-blue-900 text-xs"
                                                title="View price history"
                                            >
                                                History
                                            </button>
                                        </div>
                                        @if($prices[$action->value][$years]['latest'])
                                            <div class="text-xs text-gray-500">
                                                Updated: {{ $prices[$action->value][$years]['latest']->effective_date->format('M d, Y') }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-sm text-gray-400">Not set</span>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($showHistory && $priceHistory->count() > 0)
        <div class="mt-6 bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">
                    Price History - {{ ucfirst($historyAction) }} ({{ $historyYears }} {{ Str::plural('year', $historyYears) }})
                </h3>
                <button
                    wire:click="$set('showHistory', false)"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Effective Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Change
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($priceHistory as $history)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $history->effective_date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format($history->price, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @php
                                        $change = $history->getPriceChange();
                                    @endphp
                                    @if($change !== null)
                                        @if($change > 0)
                                            <span class="text-red-600">+{{ number_format($change, 1) }}%</span>
                                        @else
                                            <span class="text-green-600">{{ number_format($change, 1) }}%</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">Initial price</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@script
<script>
    $wire.on('price-updated', (event) => {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { type: 'success', message: event.message }
        }));
    });
</script>
@endscript
