<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Search Header -->
    <div class="text-center py-12">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Find Your Perfect Domain
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
            Search for available domains and register them instantly
        </p>

        <!-- Search Form -->
        <div class="max-w-3xl mx-auto">
            <form wire:submit.prevent="search" class="relative">
                <div class="flex flex-col sm:flex-row gap-4">
                    <!-- Search Input -->
                    <div class="flex-1">
                        <input 
                            type="text" 
                            wire:model="searchQuery"
                            placeholder="example.com (or multiple domains separated by commas)"
                            class="w-full px-6 py-4 text-lg border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
                            aria-label="Domain search input"
                        >
                        @error('searchQuery')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Years Selection -->
                    <div class="flex items-center gap-2">
                        <select 
                            wire:model.live="years"
                            class="px-4 py-4 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
                            aria-label="Registration years"
                        >
                            <option value="1">1 Year</option>
                            <option value="2">2 Years</option>
                            <option value="3">3 Years</option>
                            <option value="4">4 Years</option>
                            <option value="5">5 Years</option>
                            <option value="10">10 Years</option>
                        </select>

                        <!-- Search Button -->
                        <button 
                            type="submit"
                            class="px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                            wire:loading.attr="disabled"
                            aria-label="Search domains"
                        >
                            <span wire:loading.remove wire:target="search">Search</span>
                            <span wire:loading wire:target="search" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Searching...
                            </span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Error Message -->
            @if($errorMessage)
                <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-red-800 dark:text-red-200">{{ $errorMessage }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Loading State -->
    <div wire:loading wire:target="search" class="text-center py-8">
        <div class="inline-block">
            <svg class="animate-spin h-12 w-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-gray-600 dark:text-gray-400">Checking domain availability...</p>
        </div>
    </div>

    <!-- Search Results -->
    @if($hasSearched && !empty($searchResults))
        <div wire:loading.remove wire:target="search" class="py-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Search Results ({{ count($searchResults) }})
                    </h2>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($searchResults as $result)
                        <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <!-- Domain Name -->
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $result['domain'] }}
                                    </h3>
                                    @if(isset($result['registrar']))
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            via {{ $result['registrar'] }}
                                        </p>
                                    @endif
                                </div>

                                <!-- Status Badge -->
                                <div class="flex items-center gap-4">
                                    @if(isset($result['error']))
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $result['error'] }}
                                        </span>
                                    @elseif($result['available'])
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Available
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            Taken
                                        </span>
                                    @endif

                                    <!-- Price & Action -->
                                    @if($result['available'] && isset($result['price']))
                                        <div class="flex items-center gap-4">
                                            <div class="text-right">
                                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                                    ${{ $result['price']['register'] }}
                                                </p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    for {{ $result['years'] }} {{ $result['years'] == 1 ? 'year' : 'years' }}
                                                </p>
                                            </div>
                                            <button 
                                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors duration-200"
                                                aria-label="Register {{ $result['domain'] }}"
                                            >
                                                Register
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- No Results State -->
    @if($hasSearched && empty($searchResults) && !$errorMessage)
        <div wire:loading.remove wire:target="search" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No results found</h3>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Try a different search term</p>
        </div>
    @endif

    <!-- Suggestions -->
    @if($showSuggestions && !empty($suggestions))
        <div wire:loading.remove wire:target="search" class="py-8">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow-lg overflow-hidden">
                <div class="px-6 py-4 bg-blue-100 dark:bg-blue-900/40 border-b border-blue-200 dark:border-blue-800">
                    <h2 class="text-xl font-semibold text-blue-900 dark:text-blue-100">
                        💡 Alternative Suggestions
                    </h2>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        Your domain is taken, but these alternatives are available
                    </p>
                </div>

                <div class="divide-y divide-blue-200 dark:divide-blue-800">
                    @foreach($suggestions as $suggestion)
                        <div class="px-6 py-4 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="flex-1">
                                    <button 
                                        wire:click="searchDomain('{{ $suggestion['domain'] }}')"
                                        class="text-lg font-semibold text-blue-900 dark:text-blue-100 hover:underline text-left"
                                    >
                                        {{ $suggestion['domain'] }}
                                    </button>
                                </div>

                                <div class="flex items-center gap-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        Available
                                    </span>

                                    @if(isset($suggestion['price']))
                                        <div class="text-right">
                                            <p class="text-xl font-bold text-blue-900 dark:text-blue-100">
                                                ${{ $suggestion['price']['register'] }}
                                            </p>
                                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                                per year
                                            </p>
                                        </div>
                                        <button 
                                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors duration-200"
                                            aria-label="Register {{ $suggestion['domain'] }}"
                                        >
                                            Register
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Empty State (No search yet) -->
    @if(!$hasSearched)
        <div wire:loading.remove wire:target="search" class="text-center py-12 max-w-2xl mx-auto">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-8">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Start Your Domain Search</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Enter a domain name above to check availability. You can search for multiple domains at once by separating them with commas.
                </p>
                <div class="text-sm text-gray-500 dark:text-gray-400 space-y-2">
                    <p><strong>Examples:</strong></p>
                    <p>• example.com</p>
                    <p>• mysite.io, mysite.com, mysite.net</p>
                    <p>• awesome-startup.app</p>
                </div>
            </div>
        </div>
    @endif
</div>
