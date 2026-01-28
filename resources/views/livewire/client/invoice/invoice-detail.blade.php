<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Invoice Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Invoice {{ $invoice->invoice_number }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Issued on {{ $invoice->issued_at?->format('F d, Y') ?? 'N/A' }}
                </p>
            </div>
            <div class="text-right">
                @if($invoice->status->value === 'paid')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                    {{ $invoice->status->label() }}
                </span>
                @elseif($invoice->status->value === 'failed')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                    {{ $invoice->status->label() }}
                </span>
                @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                    {{ $invoice->status->label() }}
                </span>
                @endif
            </div>
        </div>

        <!-- From / To Information -->
        <div class="grid grid-cols-2 gap-6 mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">From</h3>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    @if(partnerBranding())
                    <p class="font-medium text-gray-900 dark:text-white">{{ partnerBranding()->email_sender_name }}</p>
                    @if(partnerBranding()->support_email)
                    <p>{{ partnerBranding()->support_email }}</p>
                    @endif
                    @if(partnerBranding()->support_phone)
                    <p>{{ partnerBranding()->support_phone }}</p>
                    @endif
                    @endif
                </div>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">To</h3>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <p class="font-medium text-gray-900 dark:text-white">{{ $invoice->client->name }}</p>
                    <p>{{ $invoice->client->email }}</p>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Items</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Description
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Quantity
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Unit Price
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($invoice->items as $item)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $item->description }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">
                                {{ $item->quantity }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">
                                ${{ number_format($item->unit_price, 2) }}
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white text-right">
                                ${{ number_format($item->total, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Totals -->
        <div class="flex justify-end">
            <div class="w-64">
                <div class="flex justify-between py-2 text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                    <span class="font-medium text-gray-900 dark:text-white">${{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Tax</span>
                    <span class="font-medium text-gray-900 dark:text-white">${{ number_format($invoice->tax, 2) }}</span>
                </div>
                <div class="flex justify-between py-3 border-t border-gray-200 dark:border-gray-700 text-base">
                    <span class="font-semibold text-gray-900 dark:text-white">Total</span>
                    <span class="font-bold text-gray-900 dark:text-white">${{ number_format($invoice->total, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Information -->
        @if($invoice->status->value === 'paid')
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-300">Payment Received</h3>
                        <div class="mt-2 text-sm text-green-700 dark:text-green-400">
                            <p>This invoice was paid on {{ $invoice->paid_at->format('F d, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Actions -->
        <div class="mt-6 flex justify-end space-x-3">
            <button wire:click="print" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print
            </button>
            <button wire:click="downloadPdf" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Download PDF
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('print-invoice', () => {
            window.print();
        });
    });
</script>
@endpush
