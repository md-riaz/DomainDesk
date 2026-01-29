<div class="px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Pricing Configuration</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Configure markup rules for domain pricing. Set fixed amounts or percentage markups for each TLD.
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

        @if (session()->has('error'))
            <div class="mb-6 rounded-md bg-red-50 dark:bg-red-900/20 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Quick Templates -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Templates</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button wire:click="applyTemplate('add_20_percent')" type="button" class="flex items-center justify-center px-4 py-3 border-2 border-blue-300 dark:border-blue-700 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition">
                    <div class="text-center">
                        <p class="font-semibold text-gray-900 dark:text-white">Add 20% to All</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Standard markup</p>
                    </div>
                </button>
                <button wire:click="applyTemplate('add_5_dollars')" type="button" class="flex items-center justify-center px-4 py-3 border-2 border-green-300 dark:border-green-700 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 transition">
                    <div class="text-center">
                        <p class="font-semibold text-gray-900 dark:text-white">Add ৳500 to All</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Fixed amount</p>
                    </div>
                </button>
                <button wire:click="applyTemplate('premium_50_percent')" type="button" class="flex items-center justify-center px-4 py-3 border-2 border-purple-300 dark:border-purple-700 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-900/20 transition">
                    <div class="text-center">
                        <p class="font-semibold text-gray-900 dark:text-white">Premium +50%</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">High margin</p>
                    </div>
                </button>
            </div>
        </div>

        <!-- Price Preview Calculator -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Price Preview Calculator</h2>
            <form wire:submit.prevent="calculatePreview" class="flex gap-4 items-start">
                <div class="flex-1">
                    <input type="text" wire:model="previewDomain" placeholder="Enter domain (e.g., example.com)" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('previewDomain') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Calculate Price
                </button>
            </form>

            @if ($showPreview && !empty($previewPrices))
                <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="font-medium text-gray-900 dark:text-white mb-4">Pricing for {{ $previewPrices['tld'] ?? '' }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach (['register' => 'Registration', 'renew' => 'Renewal', 'transfer' => 'Transfer'] as $action => $label)
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-3">{{ $label }}</h4>
                                <div class="space-y-2">
                                    @foreach ($previewPrices[$action] ?? [] as $years => $price)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">{{ $years }} year{{ $years > 1 ? 's' : '' }}</span>
                                            <span class="font-medium text-gray-900 dark:text-white">${{ $price }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button wire:click="closePreview" type="button" class="mt-4 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        Close Preview
                    </button>
                </div>
            @endif
        </div>

        <!-- Filters & Actions -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <div class="flex flex-col md:flex-row gap-4 justify-between items-start md:items-center">
                <div class="flex flex-col sm:flex-row gap-4 flex-1">
                    <!-- Search -->
                    <div class="flex-1">
                        <input type="text" wire:model.live="search" placeholder="Search TLDs..." class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Filter -->
                    <select wire:model.live="filter" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="all">All TLDs</option>
                        <option value="with_rules">With Rules</option>
                        <option value="without_rules">Without Rules</option>
                    </select>
                </div>

                <!-- Bulk Actions -->
                <div class="flex gap-2">
                    <button wire:click="toggleBulkForm" type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Bulk Apply
                    </button>
                    <button wire:click="clearAllRules" type="button" onclick="return confirm('Are you sure you want to clear all pricing rules?')" class="inline-flex items-center px-4 py-2 border border-red-300 dark:border-red-700 text-sm font-medium rounded-md text-red-700 dark:text-red-400 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Clear All Rules
                    </button>
                </div>
            </div>

            <!-- Bulk Form -->
            @if ($showBulkForm)
                <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Apply Bulk Markup</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Markup Type</label>
                            <select wire:model="bulkMarkupType" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Markup Value {{ $bulkMarkupType === 'percentage' ? '(%)' : '($)' }}
                            </label>
                            <input type="number" wire:model="bulkMarkupValue" step="0.01" min="0" max="1000" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('bulkMarkupValue') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-end">
                            <button wire:click="applyBulkMarkup" type="button" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Apply to Selected ({{ count($selectedTlds) }})
                            </button>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Select TLDs from the table below to apply bulk markup</p>
                </div>
            @endif
        </div>

        <!-- TLD Pricing Table -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left">
                                <input type="checkbox" wire:model="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                TLD
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Base Price (1yr)
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Markup Type
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Markup Value
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Final Price
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($tlds as $tld)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" wire:model="selectedTlds" value="{{ $tld->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">.{{ $tld->extension }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    ${{ number_format($tld->base_price, 2) }}
                                </td>

                                @if (isset($editingRules[$tld->id]))
                                    <!-- Edit Mode -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <select wire:model="editingRules.{{ $tld->id }}.markup_type" class="text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            <option value="percentage">Percentage</option>
                                            <option value="fixed">Fixed</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="number" wire:model="editingRules.{{ $tld->id }}.markup_value" step="0.01" min="0" max="1000" class="w-24 text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        @error("editingRules.{$tld->id}.markup_value") 
                                            <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> 
                                        @enderror
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        -
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button wire:click="saveRule({{ $tld->id }})" type="button" class="text-green-600 hover:text-green-900 dark:text-green-400 mr-3">
                                            Save
                                        </button>
                                        <button wire:click="cancelEdit({{ $tld->id }})" type="button" class="text-gray-600 hover:text-gray-900 dark:text-gray-400">
                                            Cancel
                                        </button>
                                    </td>
                                @else
                                    <!-- View Mode -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($tld->current_rule)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $tld->current_rule->markup_type->value === 'percentage' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                                                {{ $tld->current_rule->markup_type->label() }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        @if ($tld->current_rule)
                                            {{ $tld->current_rule->markup_type->value === 'percentage' ? $tld->current_rule->markup_value . '%' : '$' . number_format($tld->current_rule->markup_value, 2) }}
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold {{ $tld->current_rule ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">
                                            ${{ number_format($tld->final_price, 2) }}
                                        </span>
                                        @if ($tld->current_rule)
                                            <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                                (+${{ number_format($tld->final_price - $tld->base_price, 2) }})
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button wire:click="editRule({{ $tld->id }})" type="button" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mr-3">
                                            {{ $tld->current_rule ? 'Edit' : 'Add Rule' }}
                                        </button>
                                        @if ($tld->current_rule)
                                            <button wire:click="resetRule({{ $tld->id }})" type="button" onclick="return confirm('Reset pricing to base price?')" class="text-red-600 hover:text-red-900 dark:text-red-400">
                                                Reset
                                            </button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No TLDs found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $tlds->links() }}
            </div>
        </div>
    </div>
</div>
