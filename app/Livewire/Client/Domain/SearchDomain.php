<?php

namespace App\Livewire\Client\Domain;

use App\Services\DomainSearchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SearchDomain extends Component
{
    /**
     * Rate limit: maximum attempts per window
     */
    const RATE_LIMIT_ATTEMPTS = 10;

    /**
     * Rate limit: window in seconds (1 minute)
     */
    const RATE_LIMIT_DECAY = 60;

    #[Validate('required|string|min:3|max:1000')]
    public string $searchQuery = '';

    public array $searchResults = [];
    public array $suggestions = [];
    public bool $hasSearched = false;
    public bool $showSuggestions = false;
    public ?string $errorMessage = null;
    public int $years = 1;

    public function mount()
    {
        $this->years = 1;
    }

    /**
     * Search for domains
     */
    public function search()
    {
        // Rate limiting - only authenticated users can access this route
        $key = 'domain-search:' . Auth::id();
        
        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            $this->errorMessage = "Too many searches. Please try again in {$seconds} seconds.";
            return;
        }

        RateLimiter::hit($key, self::RATE_LIMIT_DECAY);

        // Validate
        $this->validate();

        $this->errorMessage = null;
        $this->searchResults = [];
        $this->suggestions = [];
        $this->showSuggestions = false;
        $this->hasSearched = true;

        /** @var DomainSearchService $searchService */
        $searchService = app(DomainSearchService::class);

        // Parse bulk input
        $domains = $searchService->parseBulkInput($this->searchQuery);

        if (empty($domains)) {
            $this->errorMessage = 'Please enter at least one valid domain name.';
            return;
        }

        // Get partner from context
        $partner = currentPartner();

        // Search domains
        $searchResult = $searchService->search($domains, $partner, $this->years);

        if (!$searchResult['success']) {
            $this->errorMessage = $searchResult['error'] ?? 'Search failed';
            return;
        }

        $this->searchResults = $searchResult['results'] ?? [];

        // If single domain search and domain is taken, show suggestions
        if (count($domains) === 1 && count($this->searchResults) === 1) {
            $result = $this->searchResults[0];
            
            if (!($result['available'] ?? false) && !isset($result['error'])) {
                $this->loadSuggestions($domains[0]);
            }
        }
    }

    /**
     * Load domain suggestions
     */
    protected function loadSuggestions(string $domain)
    {
        /** @var DomainSearchService $searchService */
        $searchService = app(DomainSearchService::class);
        $partner = currentPartner();

        $this->suggestions = $searchService->getSuggestions($domain, $partner, 10);
        $this->showSuggestions = !empty($this->suggestions);
    }

    /**
     * Clear search results
     */
    public function clear()
    {
        $this->reset(['searchQuery', 'searchResults', 'suggestions', 'hasSearched', 'showSuggestions', 'errorMessage']);
    }

    /**
     * Update years
     */
    public function updatedYears()
    {
        // Re-search if we have results
        if ($this->hasSearched && !empty($this->searchQuery)) {
            $this->search();
        }
    }

    /**
     * Search for a specific domain (used for clicking on suggestions)
     */
    public function searchDomain(string $domain)
    {
        $this->searchQuery = $domain;
        $this->search();
    }

    public function render()
    {
        return view('livewire.client.domain.search-domain')
            ->layout('layouts.client');
    }
}
