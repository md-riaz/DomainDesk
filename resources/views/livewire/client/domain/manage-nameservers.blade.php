<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6">
            <h1 class="text-3xl font-bold text-white">Manage Nameservers</h1>
            <p class="text-indigo-100 mt-2">Configure nameservers for {{ $domain->name }}</p>
        </div>

        <!-- Info Banner -->
        <div class="mx-8 mt-6 bg-blue-50 border-l-4 border-blue-400 p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-blue-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm text-blue-700">
                        Nameserver changes can take 24-48 hours to propagate globally. You need at least 2 nameservers (maximum 4).
                    </p>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        @if ($successMessage)
            <div class="mx-8 mt-6 bg-green-50 border-l-4 border-green-500 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-green-700">{{ $successMessage }}</p>
                </div>
            </div>
        @endif

        <!-- Error Message -->
        @if ($errorMessage)
            <div class="mx-8 mt-6 bg-red-50 border-l-4 border-red-500 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-red-700">{{ $errorMessage }}</p>
                </div>
            </div>
        @endif

        <!-- Form -->
        <div class="px-8 py-6">
            <form wire:submit.prevent="save">
                <!-- Nameservers -->
                <div class="space-y-4 mb-6">
                    @foreach ($nameservers as $index => $nameserver)
                        <div class="flex items-center space-x-3">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Nameserver {{ $index + 1 }}
                                    @if ($index < 2)
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="nameservers.{{ $index }}"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    placeholder="ns{{ $index + 1 }}.example.com"
                                    {{ $isLoading ? 'disabled' : '' }}
                                >
                            </div>
                            
                            @if ($index >= 2 && count($nameservers) > 2)
                                <button 
                                    type="button"
                                    wire:click="removeNameserver({{ $index }})"
                                    class="mt-6 p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition"
                                    {{ $isLoading ? 'disabled' : '' }}
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endforeach

                    @if (count($nameservers) < 4)
                        <button 
                            type="button"
                            wire:click="addNameserver"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition"
                            {{ $isLoading ? 'disabled' : '' }}
                        >
                            <svg class="h-5 w-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Nameserver
                        </button>
                    @endif
                </div>

                <!-- Use Default Button -->
                <div class="mb-6">
                    <button 
                        type="button"
                        wire:click="useDefaults"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                        {{ $isLoading ? 'disabled' : '' }}
                    >
                        <svg class="h-5 w-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Use Default Nameservers
                    </button>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <button 
                        type="button"
                        wire:click="sync"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition"
                        wire:loading.attr="disabled"
                        wire:target="sync"
                    >
                        <svg class="h-5 w-5 mr-1 {{ $isSyncing ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Sync from Registrar
                    </button>

                    <div class="flex space-x-3">
                        <a 
                            href="{{ route('client.domains.show', $domain) }}"
                            class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition"
                        >
                            Cancel
                        </a>
                        <button 
                            type="submit"
                            class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition"
                            wire:loading.attr="disabled"
                            wire:target="save"
                        >
                            @if ($isLoading)
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Saving...
                            @else
                                Save Changes
                            @endif
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
