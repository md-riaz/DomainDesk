<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
                    Audit Logs
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Complete system audit trail with advanced filtering
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                <button wire:click="toggleAutoRefresh" type="button"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium {{ $autoRefresh ? 'bg-green-50 text-green-700 dark:bg-green-900 dark:text-green-200' : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300' }} hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ $autoRefresh ? 'Auto-Refresh On' : 'Auto-Refresh Off' }}
                </button>
                <button wire:click="exportCsv" type="button"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                    <input wire:model.live.debounce.300ms="search" type="text" id="search"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        placeholder="User email, partner name, model...">
                </div>

                <!-- Action Filter -->
                <div>
                    <label for="filterAction" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Action</label>
                    <select wire:model.live="filterAction" id="filterAction"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">All Actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}">{{ ucfirst($action) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Model Type Filter -->
                <div>
                    <label for="filterModel" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model Type</label>
                    <select wire:model.live="filterModel" id="filterModel"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">All Models</option>
                        @foreach($modelTypes as $model)
                            <option value="App\Models\{{ $model }}">{{ $model }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Partner Filter -->
                <div>
                    <label for="filterPartnerId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Partner</label>
                    <select wire:model.live="filterPartnerId" id="filterPartnerId"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">All Partners</option>
                        @foreach($partners as $partner)
                            <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Role Filter -->
                <div>
                    <label for="filterRole" class="block text-sm font-medium text-gray-700 dark:text-gray-300">User Role</label>
                    <select wire:model.live="filterRole" id="filterRole"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}">{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label for="dateFrom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date From</label>
                    <input wire:model.live="dateFrom" type="date" id="dateFrom"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <!-- Date To -->
                <div>
                    <label for="dateTo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date To</label>
                    <input wire:model.live="dateTo" type="date" id="dateTo"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <!-- Per Page -->
                <div>
                    <label for="perPage" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Per Page</label>
                    <select wire:model.live="perPage" id="perPage"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <!-- Reset Button -->
                <div class="flex items-end">
                    <button wire:click="resetFilters" type="button"
                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Reset Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Timestamp
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                User
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Partner
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Action
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Model
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                IP Address
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $log->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $log->user?->email ?? 'System' }}</div>
                                    @if($log->user)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            @if($log->user->role->value === 'super_admin') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                            @elseif($log->user->role->value === 'partner') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                            @endif">
                                            {{ $log->user->role->label() }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $log->partner?->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($log->action === 'created') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($log->action === 'updated') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($log->action === 'deleted') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                        @endif">
                                        {{ ucfirst($log->action) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    @if($log->auditable_type)
                                        <div>{{ class_basename($log->auditable_type) }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">#{{ $log->auditable_id }}</div>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $log->ip_address }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button wire:click="viewDetails({{ $log->id }})" type="button"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No audit logs found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $logs->links() }}
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    @if($showDetailsModal && $selectedLog)
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" wire:click="closeDetailsModal"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Audit Log Details
                                </h3>
                                
                                <div class="mt-4 space-y-4">
                                    <!-- Basic Info -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Timestamp</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedLog->created_at->format('Y-m-d H:i:s') }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">User</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedLog->user?->email ?? 'System' }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Partner</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedLog->partner?->name ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Action</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($selectedLog->action) }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                                {{ $selectedLog->auditable_type ? class_basename($selectedLog->auditable_type) : 'N/A' }}
                                                {{ $selectedLog->auditable_id ? '#' . $selectedLog->auditable_id : '' }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">IP Address</label>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedLog->ip_address }}</p>
                                        </div>
                                    </div>

                                    <!-- User Agent -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">User Agent</label>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white break-all">{{ $selectedLog->user_agent }}</p>
                                    </div>

                                    <!-- Changes -->
                                    @if(!empty($selectedLog->changes))
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Changes</label>
                                            <div class="bg-gray-50 dark:bg-gray-900 rounded-md p-4 space-y-2">
                                                @foreach($selectedLog->changes as $key => $change)
                                                    <div class="border-b border-gray-200 dark:border-gray-700 pb-2 last:border-0">
                                                        <span class="font-medium text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                        <div class="mt-1 flex items-center space-x-2 text-sm">
                                                            <span class="text-red-600 dark:text-red-400 line-through">{{ json_encode($change['old']) }}</span>
                                                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                            </svg>
                                                            <span class="text-green-600 dark:text-green-400">{{ json_encode($change['new']) }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Related Logs -->
                                    @if($relatedLogs->isNotEmpty())
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Related Logs</label>
                                            <div class="bg-gray-50 dark:bg-gray-900 rounded-md p-4 space-y-2">
                                                @foreach($relatedLogs as $related)
                                                    <div class="text-sm">
                                                        <span class="text-gray-500 dark:text-gray-400">{{ $related->created_at->format('H:i:s') }}</span>
                                                        -
                                                        <span class="text-gray-900 dark:text-white">{{ ucfirst($related->action) }}</span>
                                                        by
                                                        <span class="text-gray-900 dark:text-white">{{ $related->user?->email ?? 'System' }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="closeDetailsModal" type="button"
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Auto-refresh script -->
    @if($autoRefresh)
        <script>
            setInterval(() => {
                @this.call('$refresh');
            }, 30000);
        </script>
    @endif
</div>
