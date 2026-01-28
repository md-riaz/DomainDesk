<?php

namespace App\Livewire\Client\Domain;

use App\Enums\DnsRecordType;
use App\Livewire\Concerns\HasPartnerContext;
use App\Models\Domain;
use App\Models\DomainDnsRecord;
use App\Services\DnsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ManageDns extends Component
{
    use HasPartnerContext, AuthorizesRequests;

    public Domain $domain;
    public ?DnsRecordType $filterType = null;
    public bool $showAddModal = false;
    public bool $showEditModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingRecordId = null;
    public ?int $deletingRecordId = null;

    // Form fields
    public string $recordType = 'A';
    public string $recordName = '@';
    public string $recordValue = '';
    public int $recordTtl = 3600;
    public ?int $recordPriority = null;

    // UI state
    public bool $isLoading = false;
    public bool $isSyncing = false;
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    protected DnsService $dnsService;

    public function boot(DnsService $dnsService)
    {
        $this->dnsService = $dnsService;
    }

    public function mount(Domain $domain)
    {
        $this->domain = $domain;
        
        // Authorization check
        $this->authorize('update', $domain);
    }

    public function getDnsRecordsProperty()
    {
        return $this->dnsService->getDnsRecords($this->domain, $this->filterType);
    }

    public function getRecordTypesProperty()
    {
        return DnsRecordType::cases();
    }

    public function filterByType(?string $type)
    {
        $this->filterType = $type ? DnsRecordType::from($type) : null;
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->showAddModal = true;
        $this->errorMessage = null;
    }

    public function closeAddModal()
    {
        $this->showAddModal = false;
        $this->resetForm();
    }

    public function openEditModal($recordId)
    {
        $record = DomainDnsRecord::findOrFail($recordId);
        
        if ($record->domain_id !== $this->domain->id) {
            $this->errorMessage = 'Invalid record.';
            return;
        }

        $this->editingRecordId = $recordId;
        $this->recordType = $record->type->value;
        $this->recordName = $record->name;
        $this->recordValue = $record->value;
        $this->recordTtl = $record->ttl;
        $this->recordPriority = $record->priority;
        
        $this->showEditModal = true;
        $this->errorMessage = null;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingRecordId = null;
        $this->resetForm();
    }

    public function openDeleteModal($recordId)
    {
        $this->deletingRecordId = $recordId;
        $this->showDeleteModal = true;
        $this->errorMessage = null;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingRecordId = null;
    }

    public function addRecord()
    {
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->isLoading = true;

        try {
            $data = [
                'type' => $this->recordType,
                'name' => $this->recordName,
                'value' => $this->recordValue,
                'ttl' => $this->recordTtl,
                'priority' => $this->recordPriority,
            ];

            $result = $this->dnsService->addDnsRecord(
                $this->domain,
                $data,
                Auth::id()
            );

            if ($result['success']) {
                $this->successMessage = $result['message'];
                $this->closeAddModal();
            } else {
                $this->errorMessage = $result['message'];
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first();
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while adding the DNS record.';
        } finally {
            $this->isLoading = false;
        }
    }

    public function updateRecord()
    {
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->isLoading = true;

        try {
            $record = DomainDnsRecord::findOrFail($this->editingRecordId);
            
            if ($record->domain_id !== $this->domain->id) {
                $this->errorMessage = 'Invalid record.';
                return;
            }

            $data = [
                'type' => $this->recordType,
                'name' => $this->recordName,
                'value' => $this->recordValue,
                'ttl' => $this->recordTtl,
                'priority' => $this->recordPriority,
            ];

            $result = $this->dnsService->updateDnsRecord(
                $record,
                $data,
                Auth::id()
            );

            if ($result['success']) {
                $this->successMessage = $result['message'];
                $this->closeEditModal();
            } else {
                $this->errorMessage = $result['message'];
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first();
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while updating the DNS record.';
        } finally {
            $this->isLoading = false;
        }
    }

    public function deleteRecord()
    {
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->isLoading = true;

        try {
            $record = DomainDnsRecord::findOrFail($this->deletingRecordId);
            
            if ($record->domain_id !== $this->domain->id) {
                $this->errorMessage = 'Invalid record.';
                return;
            }

            $result = $this->dnsService->deleteDnsRecord($record, Auth::id());

            if ($result['success']) {
                $this->successMessage = $result['message'];
                $this->closeDeleteModal();
            } else {
                $this->errorMessage = $result['message'];
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'An error occurred while deleting the DNS record.';
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
            $result = $this->dnsService->syncDnsRecords($this->domain);

            if ($result['success']) {
                $this->successMessage = $result['message'] . ' (' . $result['count'] . ' records)';
            } else {
                $this->errorMessage = $result['message'];
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to sync DNS records from registrar.';
        } finally {
            $this->isSyncing = false;
        }
    }

    protected function resetForm()
    {
        $this->recordType = 'A';
        $this->recordName = '@';
        $this->recordValue = '';
        $this->recordTtl = 3600;
        $this->recordPriority = null;
    }

    public function updatedRecordType()
    {
        // Reset priority when changing type
        $type = DnsRecordType::from($this->recordType);
        if (!$type->supportsPriority()) {
            $this->recordPriority = null;
        }
    }

    public function getValuePlaceholder()
    {
        return match($this->recordType) {
            'A' => '192.0.2.1',
            'AAAA' => '2001:0db8:85a3::8a2e:0370:7334',
            'CNAME' => 'example.com',
            'MX' => 'mail.example.com',
            'TXT' => 'v=spf1 include:_spf.example.com ~all',
            'NS' => 'ns1.example.com',
            'SRV' => '10 5060 sipserver.example.com',
            default => 'Enter value',
        };
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.client.domain.manage-dns');
    }
}
