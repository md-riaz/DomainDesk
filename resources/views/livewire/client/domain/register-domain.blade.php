<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6">
            <h1 class="text-3xl font-bold text-white">Register Domain</h1>
            <p class="text-indigo-100 mt-2">Complete the steps below to register your domain</p>
        </div>

        <!-- Progress Bar -->
        <div class="bg-gray-50 px-8 py-6 border-b border-gray-200">
            <div class="flex items-center justify-between mb-4">
                @for ($i = 1; $i <= $totalSteps; $i++)
                    <div class="flex items-center {{ $i < $totalSteps ? 'flex-1' : '' }}">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 
                            {{ $currentStep >= $i ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-gray-300 text-gray-400' }}">
                            @if ($currentStep > $i)
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @else
                                <span class="font-semibold">{{ $i }}</span>
                            @endif
                        </div>
                        @if ($i < $totalSteps)
                            <div class="flex-1 h-1 mx-2 {{ $currentStep > $i ? 'bg-indigo-600' : 'bg-gray-300' }}"></div>
                        @endif
                    </div>
                @endfor
            </div>
            <div class="flex justify-between text-sm">
                <span class="{{ $currentStep === 1 ? 'text-indigo-600 font-semibold' : 'text-gray-600' }}">Domain</span>
                <span class="{{ $currentStep === 2 ? 'text-indigo-600 font-semibold' : 'text-gray-600' }}">Period</span>
                <span class="{{ $currentStep === 3 ? 'text-indigo-600 font-semibold' : 'text-gray-600' }}">Contacts</span>
                <span class="{{ $currentStep === 4 ? 'text-indigo-600 font-semibold' : 'text-gray-600' }}">Nameservers</span>
                <span class="{{ $currentStep === 5 ? 'text-indigo-600 font-semibold' : 'text-gray-600' }}">Review</span>
            </div>
        </div>

        <!-- Error Message -->
        @if ($errorMessage)
            <div class="mx-8 mt-6 bg-red-50 border-l-4 border-red-500 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-red-700">{{ $errorMessage }}</p>
                </div>
            </div>
        @endif

        <!-- Step Content -->
        <div class="px-8 py-6">
            <!-- Step 1: Domain Selection -->
            @if ($currentStep === 1)
                <div class="space-y-6">
                    <h2 class="text-2xl font-bold text-gray-900">Select Domain</h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Domain Name</label>
                        <input 
                            type="text" 
                            wire:model="domainName" 
                            placeholder="example.com"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            {{ $isProcessing ? 'disabled' : '' }}
                        >
                        <p class="mt-2 text-sm text-gray-500">Enter the full domain name you wish to register</p>
                    </div>
                </div>
            @endif

            <!-- Step 2: Registration Period -->
            @if ($currentStep === 2)
                <div class="space-y-6">
                    <h2 class="text-2xl font-bold text-gray-900">Choose Registration Period</h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Number of Years</label>
                        <select 
                            wire:model.live="years" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            {{ $isProcessing ? 'disabled' : '' }}
                        >
                            @for ($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'Year' : 'Years' }}</option>
                            @endfor
                        </select>
                    </div>

                    @if ($price)
                        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">Total Price:</span>
                                <span class="text-3xl font-bold text-indigo-600">${{ number_format($price, 2) }}</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">For {{ $years }} {{ $years === 1 ? 'year' : 'years' }} of registration</p>
                        </div>
                    @endif

                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            wire:model="autoRenew" 
                            id="autoRenew"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        >
                        <label for="autoRenew" class="ml-2 block text-sm text-gray-900">
                            Enable auto-renewal to prevent domain expiration
                        </label>
                    </div>
                </div>
            @endif

            <!-- Step 3: Contact Information -->
            @if ($currentStep === 3)
                <div class="space-y-6">
                    <h2 class="text-2xl font-bold text-gray-900">Contact Information</h2>
                    
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            wire:model.live="useDefaultContacts" 
                            id="useDefaultContacts"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        >
                        <label for="useDefaultContacts" class="ml-2 block text-sm text-gray-900">
                            Use my account information as default contact
                        </label>
                    </div>

                    @if (!$useDefaultContacts)
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                                <input 
                                    type="text" 
                                    wire:model="registrantContact.first_name"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                                <input 
                                    type="text" 
                                    wire:model="registrantContact.last_name"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input 
                                    type="email" 
                                    wire:model="registrantContact.email"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                                <input 
                                    type="text" 
                                    wire:model="registrantContact.phone"
                                    placeholder="+1.5555555555"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Organization</label>
                                <input 
                                    type="text" 
                                    wire:model="registrantContact.organization"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
                                <input 
                                    type="text" 
                                    wire:model="registrantContact.address"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                                <input 
                                    type="text" 
                                    wire:model="registrantContact.city"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">State/Province *</label>
                                <input 
                                    type="text" 
                                    wire:model="registrantContact.state"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code *</label>
                                <input 
                                    type="text" 
                                    wire:model="registrantContact.postal_code"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Country *</label>
                                <input 
                                    type="text" 
                                    wire:model="registrantContact.country"
                                    value="US"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>
                        </div>
                    @else
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-sm text-gray-600">Your account information will be used as the default contact for all contact types (Registrant, Administrative, Technical, and Billing).</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Step 4: Nameservers -->
            @if ($currentStep === 4)
                <div class="space-y-6">
                    <h2 class="text-2xl font-bold text-gray-900">Nameservers</h2>
                    
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            wire:model.live="useDefaultNameservers" 
                            id="useDefaultNameservers"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        >
                        <label for="useDefaultNameservers" class="ml-2 block text-sm text-gray-900">
                            Use default nameservers (recommended)
                        </label>
                    </div>

                    @if (!$useDefaultNameservers)
                        <div class="space-y-4">
                            @foreach ($nameservers as $index => $nameserver)
                                <div class="flex items-center space-x-2">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Nameserver {{ $index + 1 }} {{ $index < 2 ? '*' : '' }}</label>
                                        <input 
                                            type="text" 
                                            wire:model="nameservers.{{ $index }}"
                                            placeholder="ns{{ $index + 1 }}.example.com"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                        >
                                    </div>
                                    @if ($index >= 2)
                                        <button 
                                            type="button"
                                            wire:click="removeNameserver({{ $index }})"
                                            class="mt-8 px-3 py-2 text-red-600 hover:text-red-800"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            @endforeach

                            @if (count($nameservers) < 4)
                                <button 
                                    type="button"
                                    wire:click="addNameserver"
                                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                >
                                    + Add Another Nameserver
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-2">Default nameservers will be used:</p>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li>• ns1.domaindesk.com</li>
                                <li>• ns2.domaindesk.com</li>
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Step 5: Review & Confirm -->
            @if ($currentStep === 5)
                <div class="space-y-6">
                    <h2 class="text-2xl font-bold text-gray-900">Review & Confirm</h2>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-lg divide-y divide-gray-200">
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Domain Details</h3>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-600">Domain:</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $domainName }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-600">Registration Period:</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $years }} {{ $years === 1 ? 'Year' : 'Years' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-600">Auto-Renew:</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $autoRenew ? 'Enabled' : 'Disabled' }}</dd>
                                </div>
                            </dl>
                        </div>
                        
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Contact Information</h3>
                            <p class="text-sm text-gray-600">{{ $useDefaultContacts ? 'Using account default contacts' : 'Using custom contact information' }}</p>
                        </div>
                        
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Nameservers</h3>
                            <p class="text-sm text-gray-600">{{ $useDefaultNameservers ? 'Using default nameservers' : 'Using custom nameservers' }}</p>
                        </div>
                        
                        <div class="p-4 bg-indigo-50">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-900">Total Amount:</span>
                                <span class="text-3xl font-bold text-indigo-600">${{ number_format($price, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="h-5 w-5 text-yellow-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm text-yellow-800">This amount will be deducted from your wallet balance immediately upon registration.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <input 
                            type="checkbox" 
                            wire:model="acceptTerms" 
                            id="acceptTerms"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mt-1"
                        >
                        <label for="acceptTerms" class="ml-2 block text-sm text-gray-900">
                            I accept the <a href="#" class="text-indigo-600 hover:text-indigo-800">Terms of Service</a> and <a href="#" class="text-indigo-600 hover:text-indigo-800">Domain Registration Agreement</a>
                        </label>
                    </div>
                </div>
            @endif
        </div>

        <!-- Navigation Buttons -->
        <div class="bg-gray-50 px-8 py-6 border-t border-gray-200 flex justify-between">
            @if ($currentStep > 1)
                <button 
                    type="button"
                    wire:click="previousStep"
                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition"
                    {{ $isProcessing ? 'disabled' : '' }}
                >
                    Previous
                </button>
            @else
                <div></div>
            @endif

            @if ($currentStep < $totalSteps)
                <button 
                    type="button"
                    wire:click="nextStep"
                    class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition"
                    {{ $isProcessing ? 'disabled' : '' }}
                >
                    Next
                </button>
            @else
                <button 
                    type="button"
                    wire:click="register"
                    class="px-8 py-3 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 transition disabled:opacity-50"
                    {{ $isProcessing || !$acceptTerms ? 'disabled' : '' }}
                >
                    @if ($isProcessing)
                        <span class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    @else
                        Register Domain
                    @endif
                </button>
            @endif
        </div>
    </div>
</div>
