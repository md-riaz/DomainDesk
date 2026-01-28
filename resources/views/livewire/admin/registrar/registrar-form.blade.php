<div>
    <form wire:submit="save" class="space-y-6">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Registrar Information</h3>
            
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Registrar Name *</label>
                    <input
                        type="text"
                        wire:model="name"
                        id="name"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required
                    />
                    @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="api_class" class="block text-sm font-medium text-gray-700">API Class *</label>
                    <select
                        wire:model="api_class"
                        id="api_class"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required
                    >
                        <option value="">Select API Class</option>
                        @foreach($availableClasses as $class => $label)
                            <option value="{{ $class }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('api_class') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">API Credentials</h3>
            
            <div class="space-y-4">
                <div>
                    <label for="credentials" class="block text-sm font-medium text-gray-700 mb-2">
                        Credentials (JSON) *
                    </label>
                    <p class="text-sm text-gray-500 mb-2">
                        Enter API credentials as JSON. Common fields: <code class="bg-gray-100 px-1 py-0.5 rounded">api_key</code>, <code class="bg-gray-100 px-1 py-0.5 rounded">user_id</code>, <code class="bg-gray-100 px-1 py-0.5 rounded">password</code>
                    </p>
                    <textarea
                        wire:model="credentialsJson"
                        id="credentials"
                        rows="8"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                        placeholder='{"api_key": "your-key", "user_id": "123456"}'
                        required
                    ></textarea>
                    @error('credentialsJson') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                @if($testResult)
                    <div class="rounded-lg p-4 {{ $testResult === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                        <div class="flex items-center">
                            @if($testResult === 'success')
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-green-800 font-medium">{{ $testMessage }}</span>
                            @else
                                <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-red-800 font-medium">{{ $testMessage }}</span>
                            @endif
                        </div>
                    </div>
                @endif

                <button
                    type="button"
                    wire:click="testConnection"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                    <span wire:loading wire:target="testConnection">Testing...</span>
                </button>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Settings</h3>
            
            <div class="space-y-4">
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        wire:model="is_active"
                        id="is_active"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        Active (Can be used for domain operations)
                    </label>
                </div>

                <div class="flex items-center">
                    <input
                        type="checkbox"
                        wire:model="is_default"
                        id="is_default"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <label for="is_default" class="ml-2 block text-sm text-gray-900">
                        Default Registrar (Used for new TLDs)
                    </label>
                </div>
            </div>
        </div>

        @error('general')
            <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-800">{{ $message }}</span>
                </div>
            </div>
        @enderror

        <div class="flex items-center justify-between">
            <a
                href="{{ route('admin.registrars.list') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                Cancel
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
            >
                <span wire:loading.remove>{{ $registrar ? 'Update' : 'Create' }} Registrar</span>
                <span wire:loading>Saving...</span>
            </button>
        </div>
    </form>
</div>
