<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Shopping Cart</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Review and manage your domain selections</p>
    </div>

    <!-- Messages -->
    @if($successMessage)
    <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg p-4">
        <p class="text-sm text-green-800 dark:text-green-300">{{ $successMessage }}</p>
    </div>
    @endif

    @if($errorMessage)
    <div class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <p class="text-sm text-red-800 dark:text-red-300">{{ $errorMessage }}</p>
    </div>
    @endif

    @if($order && $order->items->count() > 0)
    <!-- Cart Items -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Domain
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Years
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Price
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($order->items as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->domain_name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $item->tld->tld }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                {{ $item->type->label() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <select wire:change="updateYears({{ $item->id }}, $event.target.value)" 
                                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                @for($y = 1; $y <= 10; $y++)
                                <option value="{{ $y }}" {{ $item->years == $y ? 'selected' : '' }}>{{ $y }} {{ str_plural('year', $y) }}</option>
                                @endfor
                            </select>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900 dark:text-white">
                            ৳{{ number_format($item->total, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="removeItem({{ $item->id }})" 
                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                Remove
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Cart Summary -->
        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center mb-4">
                <div class="flex space-x-4">
                    <button wire:click="clearCart" 
                            class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                        Clear Cart
                    </button>
                    <a href="{{ route('client.domains.search') }}" 
                       class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                        Continue Shopping
                    </a>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Items: {{ $order->items->count() }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        ৳{{ number_format($order->items->sum('total'), 2) }}
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button wire:click="proceedToCheckout" 
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm text-sm font-medium">
                    Proceed to Checkout
                </button>
            </div>
        </div>
    </div>
    @else
    <!-- Empty Cart -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Your cart is empty</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start by searching for domains</p>
            <div class="mt-6">
                <a href="{{ route('client.domains.search') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Search Domains
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
