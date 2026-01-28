<?php

namespace App\Livewire\Client\Domain;

use App\Models\Domain;
use App\Services\DomainTransferService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Transfer Status')]
class TransferStatus extends Component
{
    public Domain $domain;
    public bool $isRefreshing = false;
    public ?string $statusMessage = null;

    protected DomainTransferService $transferService;

    public function boot(DomainTransferService $transferService): void
    {
        $this->transferService = $transferService;
    }

    public function mount(Domain $domain): void
    {
        // Verify user owns this domain
        if ($domain->client_id !== Auth::id()) {
            abort(403, 'Unauthorized access to domain');
        }

        $this->domain = $domain;
    }

    public function refreshStatus(): void
    {
        $this->isRefreshing = true;
        $this->statusMessage = null;

        try {
            $result = $this->transferService->checkTransferStatus($this->domain);
            
            if ($result['success']) {
                $this->domain = $result['domain'];
                $this->statusMessage = 'Status updated successfully';
            } else {
                $this->statusMessage = $result['message'];
            }
        } catch (\Exception $e) {
            $this->statusMessage = 'Error: ' . $e->getMessage();
        } finally {
            $this->isRefreshing = false;
        }
    }

    public function cancelTransfer(): void
    {
        if (!$this->domain->canCancelTransfer()) {
            $this->statusMessage = 'Transfer cannot be cancelled at this stage';
            return;
        }

        try {
            $result = $this->transferService->cancelTransfer($this->domain, Auth::id());
            
            if ($result['success']) {
                $this->domain = $result['domain'];
                session()->flash('success', $result['message']);
                $this->redirect(route('client.domains.show', ['domain' => $this->domain->id]));
            } else {
                $this->statusMessage = $result['message'];
            }
        } catch (\Exception $e) {
            $this->statusMessage = 'Error cancelling transfer: ' . $e->getMessage();
        }
    }

    public function render()
    {
        $progressPercentage = $this->calculateProgress();
        $statusHistory = $this->getStatusHistory();

        return view('livewire.client.domain.transfer-status', [
            'progressPercentage' => $progressPercentage,
            'statusHistory' => $statusHistory,
        ]);
    }

    protected function calculateProgress(): int
    {
        if (!$this->domain->transfer_initiated_at) {
            return 0;
        }

        return match ($this->domain->status->value) {
            'pending_transfer' => 25,
            'transfer_in_progress' => 50,
            'transfer_approved' => 75,
            'transfer_completed' => 100,
            'transfer_failed', 'transfer_cancelled' => 0,
            default => 0,
        };
    }

    protected function getStatusHistory(): array
    {
        $history = [];

        if ($this->domain->transfer_initiated_at) {
            $history[] = [
                'status' => 'Initiated',
                'timestamp' => $this->domain->transfer_initiated_at,
                'message' => 'Transfer request submitted',
            ];
        }

        if ($this->domain->status->value === 'transfer_in_progress') {
            $history[] = [
                'status' => 'In Progress',
                'timestamp' => now(),
                'message' => 'Transfer is being processed',
            ];
        }

        if ($this->domain->status->value === 'transfer_approved') {
            $history[] = [
                'status' => 'Approved',
                'timestamp' => now(),
                'message' => 'Transfer approved by current owner',
            ];
        }

        if ($this->domain->transfer_completed_at) {
            $history[] = [
                'status' => 'Completed',
                'timestamp' => $this->domain->transfer_completed_at,
                'message' => 'Transfer completed successfully',
            ];
        }

        if ($this->domain->status->value === 'transfer_failed') {
            $history[] = [
                'status' => 'Failed',
                'timestamp' => now(),
                'message' => $this->domain->transfer_status_message ?? 'Transfer failed',
            ];
        }

        if ($this->domain->status->value === 'transfer_cancelled') {
            $history[] = [
                'status' => 'Cancelled',
                'timestamp' => now(),
                'message' => 'Transfer was cancelled',
            ];
        }

        return $history;
    }
}
