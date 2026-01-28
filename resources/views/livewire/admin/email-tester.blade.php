<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Email Template Tester</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Preview and test email templates with different partner brandings
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Configuration Panel -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Configuration</h3>
                
                <!-- Email Type -->
                <div class="mb-4">
                    <label for="emailType" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Email Template
                    </label>
                    <select 
                        id="emailType"
                        wire:model="emailType" 
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach($availableEmailTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Partner -->
                <div class="mb-4">
                    <label for="partner" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Partner (Branding)
                    </label>
                    <select 
                        id="partner"
                        wire:model="partnerId" 
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach($partners as $partner)
                            <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Test Mode -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Test Mode
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input 
                                type="radio" 
                                wire:model="testMode" 
                                value="preview" 
                                class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Preview Only</span>
                        </label>
                        <label class="flex items-center">
                            <input 
                                type="radio" 
                                wire:model="testMode" 
                                value="send" 
                                class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Send Test Email</span>
                        </label>
                    </div>
                </div>

                @if($testMode === 'send')
                    <!-- Recipient Email -->
                    <div class="mb-4">
                        <label for="recipientEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Recipient Email
                        </label>
                        <input 
                            type="email" 
                            id="recipientEmail"
                            wire:model="recipientEmail" 
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="test@example.com"
                        >
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="space-y-2">
                    @if($testMode === 'preview')
                        <button 
                            wire:click="previewEmail" 
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md transition"
                        >
                            Generate Preview
                        </button>
                    @else
                        <button 
                            wire:click="sendTestEmail" 
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition"
                        >
                            Send Test Email
                        </button>
                    @endif
                </div>

                <!-- Status Messages -->
                @if($sendStatus === 'success')
                    <div class="mt-4 p-3 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 rounded-md">
                        ✅ Test email sent successfully to {{ $recipientEmail }}
                    </div>
                @elseif($sendStatus && $sendStatus !== 'success')
                    <div class="mt-4 p-3 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 rounded-md">
                        ❌ {{ $sendStatus }}
                    </div>
                @endif

                <!-- Quick Info -->
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-md">
                    <h4 class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">💡 Quick Info</h4>
                    <ul class="text-xs text-blue-800 dark:text-blue-300 space-y-1">
                        <li>• Preview uses test data</li>
                        <li>• All templates are responsive</li>
                        <li>• Partner branding is applied</li>
                        <li>• Inline CSS for email clients</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Preview Panel -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Email Preview</h3>
                
                @if($previewHtml)
                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
                        <div class="bg-gray-100 dark:bg-gray-700 px-4 py-2 border-b border-gray-300 dark:border-gray-600">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Preview rendering - actual email may vary slightly in different email clients
                            </p>
                        </div>
                        <div class="bg-white dark:bg-gray-900 overflow-auto" style="max-height: 800px;">
                            <iframe 
                                srcdoc="{{ htmlspecialchars($previewHtml) }}" 
                                class="w-full border-0" 
                                style="min-height: 600px;"
                                sandbox="allow-same-origin"
                            ></iframe>
                        </div>
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No preview generated</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Click "Generate Preview" to see how the email will look
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
