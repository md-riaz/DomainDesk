<?php

namespace App\Livewire\Admin\Tld;

use App\Enums\PriceAction;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Services\Registrar\RegistrarFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class TldPricing extends Component
{
    public Tld $tld;
    public $tldId;
    
    public $selectedAction = 'register';
    public $selectedYears = 1;
    public $newPrice = '';
    public $effectiveDate = '';
    public $notes = '';
    
    public $isSyncing = false;
    public $syncResult = null;
    public $syncMessage = '';
    
    public $showHistory = false;
    public $historyAction = 'register';
    public $historyYears = 1;
    
    public $showManualOverride = false;

    public function mount($tldId)
    {
        $this->tldId = $tldId;
        $this->tld = Tld::with('registrar')->findOrFail($tldId);
        $this->effectiveDate = now()->toDateString();
    }

    public function syncPrices()
    {
        $this->isSyncing = true;
        $this->syncResult = null;
        $this->syncMessage = '';
        
        try {
            if (!$this->tld->registrar) {
                throw new \Exception('TLD has no registrar assigned');
            }
            
            $registrar = RegistrarFactory::make($this->tld->registrar->id);
            
            $this->syncResult = 'error';
            $this->syncMessage = "Price sync via registrar API not yet implemented. Use manual override to set prices.";
            
            Log::warning('TLD price sync attempted but not implemented', [
                'tld_id' => $this->tld->id,
                'registrar_id' => $this->tld->registrar->id,
            ]);
            
        } catch (\Throwable $e) {
            $this->syncResult = 'error';
            $this->syncMessage = "Sync failed: {$e->getMessage()}";
            
            Log::error('TLD price sync failed', [
                'tld_id' => $this->tld->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->isSyncing = false;
        }
    }

    public function saveManualPrice()
    {
        $this->validate([
            'newPrice' => 'required|numeric|min:0',
            'effectiveDate' => 'required|date|before_or_equal:' . now()->addDays(30)->toDateString(),
            'selectedAction' => 'required|in:register,renew,transfer',
            'selectedYears' => 'required|integer|min:1|max:10',
            'notes' => 'nullable|string|max:500',
        ]);
        
        try {
            DB::beginTransaction();
            
            $priceRecord = TldPrice::create([
                'tld_id' => $this->tld->id,
                'action' => $this->selectedAction,
                'years' => $this->selectedYears,
                'price' => $this->newPrice,
                'effective_date' => $this->effectiveDate,
            ]);
            
            DB::commit();
            
            auditLog('Manually updated TLD price', $this->tld, [
                'action' => $this->selectedAction,
                'years' => $this->selectedYears,
                'price' => $this->newPrice,
                'effective_date' => $this->effectiveDate,
                'notes' => $this->notes,
            ]);
            
            $this->showManualOverride = false;
            $this->newPrice = '';
            $this->notes = '';
            
            $this->dispatch('price-updated', [
                'message' => 'Price updated successfully'
            ]);
            
        } catch (\Throwable $e) {
            DB::rollBack();
            
            $this->addError('general', 'Failed to update price: ' . $e->getMessage());
        }
    }

    public function toggleHistory($action, $years)
    {
        $this->showHistory = !$this->showHistory;
        $this->historyAction = $action;
        $this->historyYears = $years;
    }

    public function getPriceHistory()
    {
        return TldPrice::where('tld_id', $this->tld->id)
            ->where('action', $this->historyAction)
            ->where('years', $this->historyYears)
            ->orderBy('effective_date', 'desc')
            ->limit(20)
            ->get();
    }

    public function getCurrentPrices()
    {
        $prices = [];
        
        foreach (PriceAction::cases() as $action) {
            $prices[$action->value] = [];
            
            for ($years = $this->tld->min_years; $years <= $this->tld->max_years; $years++) {
                $price = $this->tld->getBasePrice($action, $years);
                
                if ($price !== null) {
                    $prices[$action->value][$years] = [
                        'price' => $price,
                        'latest' => TldPrice::where('tld_id', $this->tld->id)
                            ->where('action', $action->value)
                            ->where('years', $years)
                            ->where('effective_date', '<=', now()->toDateString())
                            ->orderBy('effective_date', 'desc')
                            ->first(),
                    ];
                }
            }
        }
        
        return $prices;
    }

    public function render()
    {
        $prices = $this->getCurrentPrices();
        $priceHistory = $this->showHistory ? $this->getPriceHistory() : collect();

        return view('livewire.admin.tld.tld-pricing', [
            'prices' => $prices,
            'priceHistory' => $priceHistory,
            'actions' => PriceAction::cases(),
        ])->layout('layouts.admin', [
            'title' => "Pricing for .{$this->tld->extension}",
            'breadcrumbs' => [
                ['label' => 'TLDs', 'url' => route('admin.tlds.list')],
                ['label' => ".{$this->tld->extension}"],
            ],
        ]);
    }
}
