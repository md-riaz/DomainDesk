<?php

namespace App\Livewire\Client\Domain;

use App\Livewire\Concerns\HasPartnerContext;
use App\Models\Domain;
use App\Services\NameserverService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ManageNameservers extends Component
{
    use HasPartnerContext, AuthorizesRequests;

    public Domain $domain;
    public array $nameservers = [];
    public bool $useDefaultNameservers = false;
    public bool $isLoading = false;
    public bool $isSyncing = false;
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    protected NameserverService $nameserverService;

    public function boot(NameserverService $nameserverService)
    {
        $this->nameserverService = $nameserverService;
    }

    public function mount(Domain $domain)
    {
        $this->domain = $domain;
        
        // Authorization check
        $this->authorize('update', $domain);

        // Load current nameservers
        $this->loadNameservers();
    }

    public function loadNameservers()
    {
        $currentNameservers = $this->nameserverService->getNameservers($this->domain);
        
        // Ensure we have at least 2 slots, max 4
        while (count($currentNameservers) < 2) {
            $currentNameservers[] = '';
        }
        
        $this->nameservers = array_slice($currentNameservers, 0, 4);
    }

    public function addNameserver()
    {
        if (count($this->nameservers) < 4) {
            $this->nameservers[] = '';
        }
    }

    public function removeNameserver($index)
    {
        if (count($this->nameservers) > 2) {
            array_splice($this->nameservers, $index, 1);
        }
    }

    public function useDefaults()
    {
        $this->useDefaultNameservers = true;
        $defaultNameservers = $this->nameserverService->getDefaultNameservers($this->domain);
        
        $this->nameservers = [];
        foreach ($defaultNameservers as $ns) {
            $this->nameservers[] = $ns;
        }
        
        // Ensure we have exactly 2-4 nameservers
        while (count($this->nameservers) < 2) {
            $this->nameservers[] = '';
        }
        $this->nameservers = array_slice($this->nameservers, 0, 4);
    }

    public function save()
    {
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->isLoading = true;

        try {
            // Filter out empty nameservers
            $nameservers = array_filter($this->nameservers, fn($ns) => !empty(trim($ns)));

            $result = $this->nameserverService->updateNameservers(
                $this->domain,
                $nameservers,
                Auth::id()
            );

            if ($result['success']) {
                $this->successMessage = $result['message'];
                $this->loadNameservers();
                $this->useDefaultNameservers = false;
            } else {
                $this->errorMessage = $result['message'];
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first();
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while updating nameservers. Please try again.';
        } finally {
            $this->isLoading = false;
        }
    }

    public function sync()
    {
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->isSyncing = true;

        try {
            $result = $this->nameserverService->syncNameservers($this->domain);

            if ($result['success']) {
                $this->successMessage = $result['message'];
                $this->loadNameservers();
            } else {
                $this->errorMessage = $result['message'];
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to sync nameservers from registrar.';
        } finally {
            $this->isSyncing = false;
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.client.domain.manage-nameservers');
    }
}
