<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Welcome back, {{ auth()->user()->name }}</p>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Domains -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Domains</dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $metrics['total_domains'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Domains -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Active Domains</dt>
                            <dd class="text-2xl font-semibold text-green-600 dark:text-green-400">{{ $metrics['active_domains'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expiring Soon -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Expiring Soon</dt>
                            <dd class="text-2xl font-semibold text-orange-600 dark:text-orange-400">{{ $metrics['expiring_soon'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Renewals -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Pending Renewals</dt>
                            <dd class="text-2xl font-semibold text-yellow-600 dark:text-yellow-400">{{ $metrics['pending_renewals'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Quick Actions -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
                <div class="space-y-3">
                    <a href="{{ route('client.domains.search') }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg text-center transition duration-150">
                        Register New Domain
                    </a>
                    @if($metrics['expiring_soon'] > 0)
                    <a href="{{ route('client.domains.list', ['statusFilter' => 'expiring_soon']) }}" class="block w-full bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 px-4 rounded-lg text-center transition duration-150">
                        Renew Expiring Domains
                    </a>
                    @endif
                    <a href="{{ route('client.domains.list') }}" class="block w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-4 rounded-lg text-center transition duration-150">
                        View All Domains
                    </a>
                </div>

                <!-- Support Information -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Need Help?</h3>
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        @if(partnerBranding()?->support_email)
                        <p>
                            <span class="font-medium">Email:</span>
                            <a href="mailto:{{ partnerBranding()->support_email }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                {{ partnerBranding()->support_email }}
                            </a>
                        </p>
                        @endif
                        @if(partnerBranding()?->support_phone)
                        <p>
                            <span class="font-medium">Phone:</span>
                            <span>{{ partnerBranding()->support_phone }}</span>
                        </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Recent Domains -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Domains</h2>
                    <a href="{{ route('client.domains.list') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                </div>
                @if($recentDomains->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Domain</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expires</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($recentDomains as $domain)
                            <tr>
                                <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $domain->name }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm">
                                    @if($domain->status->value === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                        {{ $domain->status->label() }}
                                    </span>
                                    @elseif($domain->status->value === 'expired')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                        {{ $domain->status->label() }}
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                        {{ $domain->status->label() }}
                                    </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($domain->expires_at)
                                    {{ $domain->expires_at->format('M d, Y') }}
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('client.domains.show', $domain) }}" class="text-blue-600 dark:text-blue-400 hover:underline">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9 3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No domains yet</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by registering your first domain.</p>
                    <div class="mt-6">
                        <a href="{{ route('client.domains.search') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Search Domains
                        </a>
                    </div>
                </div>
                @endif
            </div>

            <!-- Recent Invoices -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Invoices</h2>
                    <a href="{{ route('client.invoices.list') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                </div>
                @if($recentInvoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Invoice #</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($recentInvoices as $invoice)
                            <tr>
                                <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $invoice->invoice_number }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $invoice->issued_at?->format('M d, Y') }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                    ${{ number_format($invoice->total, 2) }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm">
                                    @if($invoice->status->value === 'paid')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                        {{ $invoice->status->label() }}
                                    </span>
                                    @elseif($invoice->status->value === 'failed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                        {{ $invoice->status->label() }}
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                        {{ $invoice->status->label() }}
                                    </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('client.invoices.show', $invoice) }}" class="text-blue-600 dark:text-blue-400 hover:underline">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No invoices yet</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Your invoices will appear here.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
