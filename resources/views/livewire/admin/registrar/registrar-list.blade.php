<div>
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search registrars..."
                class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
            
            <select wire:model.live="statusFilter" class="rounded-lg border-gray-300 shadow-sm">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="default">Default</option>
            </select>
        </div>
        
        <a
            href="{{ route('admin.registrars.add') }}"
            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Registrar
        </a>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th wire:click="updateSortBy('name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        <div class="flex items-center">
                            Name
                            @if($sortBy === 'name')
                                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="{{ $sortDirection === 'asc' ? 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' : 'M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z' }}" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        API Class
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Health
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        TLDs
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Last Sync
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($registrars as $registrar)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $registrar->name }}
                                        @if($registrar->is_default)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                Default
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ class_basename($registrar->api_class) }}</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($registrar->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $health = $this->getHealthStatus($registrar->id);
                            @endphp
                            @if($health === 'operational')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <circle cx="10" cy="10" r="4"/>
                                    </svg>
                                    Operational
                                </span>
                            @elseif($health === 'error')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <circle cx="10" cy="10" r="4"/>
                                    </svg>
                                    Error
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <circle cx="10" cy="10" r="4"/>
                                    </svg>
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $registrar->tlds_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $registrar->last_sync_at?->diffForHumans() ?? 'Never' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <button
                                wire:click="testConnection({{ $registrar->id }})"
                                class="text-blue-600 hover:text-blue-900"
                                title="Test Connection"
                            >
                                Test
                            </button>
                            <a
                                href="{{ route('admin.registrars.edit', $registrar->id) }}"
                                class="text-blue-600 hover:text-blue-900"
                            >
                                Edit
                            </a>
                            <button
                                wire:click="toggleActive({{ $registrar->id }})"
                                class="text-blue-600 hover:text-blue-900"
                            >
                                {{ $registrar->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            @if(!$registrar->is_default && $registrar->is_active)
                                <button
                                    wire:click="setDefault({{ $registrar->id }})"
                                    class="text-blue-600 hover:text-blue-900"
                                >
                                    Set Default
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                            No registrars found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $registrars->links() }}
    </div>
</div>

@script
<script>
    $wire.on('registrar-tested', (event) => {
        if (event.success) {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type: 'success', message: event.message }
            }));
        } else {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type: 'error', message: event.message }
            }));
        }
    });

    $wire.on('registrar-updated', (event) => {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { type: 'success', message: event.message }
        }));
    });

    $wire.on('registrar-error', (event) => {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { type: 'error', message: event.message }
        }));
    });
</script>
@endscript
