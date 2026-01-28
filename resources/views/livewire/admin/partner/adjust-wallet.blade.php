<div class="px-4 py-5 sm:p-6">
    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
        Adjust Wallet Balance
    </h3>

    <form wire:submit="adjustBalance">
        <div class="space-y-4">
            <!-- Partner Selection (if not pre-selected) -->
            @if(!$partner)
            <div>
                <label for="partnerId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Select Partner <span class="text-red-500">*</span>
                </label>
                <select wire:model="partnerId" 
                        id="partnerId"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select a partner...</option>
                    @foreach($partners as $p)
                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->email }})</option>
                    @endforeach
                </select>
                @error('partnerId')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <!-- Transaction Type -->
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Transaction Type <span class="text-red-500">*</span>
                </label>
                <select wire:model="type" 
                        id="type"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="credit">Credit (Add funds)</option>
                    <option value="debit">Debit (Remove funds)</option>
                    <option value="adjustment">Adjustment (Can be + or -)</option>
                </select>
                @error('type')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Amount -->
            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Amount <span class="text-red-500">*</span>
                </label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 dark:text-gray-400 sm:text-sm">$</span>
                    </div>
                    <input type="number" 
                           wire:model="amount" 
                           id="amount"
                           step="0.01"
                           min="0.01"
                           placeholder="0.00"
                           class="pl-7 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                @error('amount')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                
                @if($type === 'adjustment')
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Use negative value to decrease balance, positive to increase
                </p>
                @endif
            </div>

            <!-- Reason -->
            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Reason/Notes <span class="text-red-500">*</span>
                </label>
                <textarea wire:model="reason" 
                          id="reason"
                          rows="3"
                          placeholder="Enter reason for this adjustment (minimum 10 characters)"
                          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                @error('reason')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    This will be recorded in the audit log
                </p>
            </div>

            <!-- Warning -->
            <div class="rounded-md bg-yellow-50 dark:bg-yellow-900/20 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 dark:text-yellow-200">
                            This action will be logged and is irreversible. Please ensure the amount and reason are correct.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
            <button type="submit" 
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                Confirm Adjustment
            </button>
            <button type="button" 
                    wire:click="cancel"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                Cancel
            </button>
        </div>
    </form>
</div>
