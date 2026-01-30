<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Invoice {{ $invoice->invoice_number }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Issued on {{ $invoice->issued_at->format('F d, Y') }}</p>
        </div>
        <div class="flex space-x-2">
            <button wire:click="downloadPdf" 
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Download PDF
            </button>
            <button wire:click="sendToClient" 
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Send to Client
            </button>
            <button wire:click="print" 
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print
            </button>
        </div>
    </div>

    <!-- Invoice Card -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <div class="p-6">
            <!-- Invoice Header -->
            <div class="flex justify-between mb-8">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Bill To:</h2>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $invoice->client->name }}</p>
                    <p class="text-gray-600 dark:text-gray-400">{{ $invoice->client->email }}</p>
                </div>
                <div class="text-right">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Invoice Details:</h2>
                    <p class="text-gray-600 dark:text-gray-400">Invoice #: <span class="text-gray-900 dark:text-white font-medium">{{ $invoice->invoice_number }}</span></p>
                    <p class="text-gray-600 dark:text-gray-400">Issued: <span class="text-gray-900 dark:text-white">{{ $invoice->issued_at->format('M d, Y') }}</span></p>
                    @if($invoice->due_at)
                    <p class="text-gray-600 dark:text-gray-400">Due: <span class="text-gray-900 dark:text-white">{{ $invoice->due_at->format('M d, Y') }}</span></p>
                    @endif
                    <div class="mt-2">
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                            {{ $invoice->status->value === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                            {{ $invoice->status->value === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                            {{ $invoice->status->value === 'overdue' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                            {{ $invoice->status->value === 'cancelled' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300' : '' }}">
                            {{ ucfirst($invoice->status->value) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Invoice Items -->
            <div class="mb-8">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Description
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Quantity
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Unit Price
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($invoice->items as $item)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white font-medium">{{ $item->description }}</div>
                                @if($item->details)
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $item->details }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white">
                                {{ $item->quantity }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white">
                                ৳{{ number_format($item->unit_price, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white font-medium">
                                ৳{{ number_format($item->amount, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Invoice Totals -->
            <div class="flex justify-end">
                <div class="w-full max-w-xs">
                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                        <span class="text-gray-900 dark:text-white font-medium">৳{{ number_format($invoice->subtotal, 2) }}</span>
                    </div>
                    @if($invoice->tax > 0)
                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Tax:</span>
                        <span class="text-gray-900 dark:text-white font-medium">৳{{ number_format($invoice->tax, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-2 text-base font-semibold border-t border-gray-200 dark:border-gray-700">
                        <span class="text-gray-900 dark:text-white">Total:</span>
                        <span class="text-gray-900 dark:text-white">৳{{ number_format($invoice->total, 2) }}</span>
                    </div>
                    @if($invoice->paid_at)
                    <div class="flex justify-between py-2 text-sm text-green-600 dark:text-green-400">
                        <span>Paid on:</span>
                        <span>{{ $invoice->paid_at->format('M d, Y') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actions -->
        @if($invoice->status->value === 'pending')
        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-end">
                <button wire:click="markAsPaid" 
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md shadow-sm text-sm font-medium">
                    Mark as Paid
                </button>
            </div>
        </div>
        @endif
    </div>

    <!-- Back Button -->
    <div class="mt-6">
        <a href="{{ route('partner.invoices.list') }}" 
           class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 text-sm font-medium">
            ← Back to Invoices
        </a>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('print-invoice', () => {
            window.print();
        });
    });
</script>
@endpush
