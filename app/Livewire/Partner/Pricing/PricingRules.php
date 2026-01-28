<?php

namespace App\Livewire\Partner\Pricing;

use App\Enums\MarkupType;
use App\Enums\PriceAction;
use App\Models\PartnerPricingRule;
use App\Models\Tld;
use App\Services\PricingService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PricingRules extends Component
{
    use WithPagination;

    public $search = '';
    public $filter = 'all'; // all, with_rules, without_rules
    public $selectedTlds = [];
    public $bulkMarkupType = 'percentage';
    public $bulkMarkupValue = '';
    public $showBulkForm = false;
    public $previewDomain = '';
    public $showPreview = false;
    public $previewPrices = [];
    
    // Inline editing
    public $editingRules = [];
    public $originalRules = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filter' => ['except' => 'all'],
    ];

    public function mount()
    {
        //
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilter()
    {
        $this->resetPage();
    }

    public function getTldsProperty()
    {
        $partner = currentPartner();
        
        $query = Tld::with(['prices' => function ($q) {
            $q->where('action', 'register')
              ->where('years', 1)
              ->where('effective_date', '<=', now()->toDateString())
              ->orderBy('effective_date', 'desc')
              ->limit(1);
        }])->active();

        if ($this->search) {
            $query->where('extension', 'like', '%' . $this->search . '%');
        }

        // Apply filter with subquery before pagination
        if ($this->filter === 'with_rules') {
            $query->whereHas('pricingRules', function ($q) use ($partner) {
                $q->where('partner_id', $partner->id)
                  ->whereNull('duration');
            });
        } elseif ($this->filter === 'without_rules') {
            $query->whereDoesntHave('pricingRules', function ($q) use ($partner) {
                $q->where('partner_id', $partner->id)
                  ->whereNull('duration');
            });
        }

        $tlds = $query->orderBy('extension')->paginate(20);

        // Load pricing rules for current partner
        $rules = PartnerPricingRule::where('partner_id', $partner->id)
            ->whereIn('tld_id', $tlds->pluck('id'))
            ->whereNull('duration') // Only get general rules
            ->get()
            ->keyBy('tld_id');

        // Attach rules to TLDs for easy access
        foreach ($tlds as $tld) {
            $tld->current_rule = $rules->get($tld->id);
            $tld->base_price = $tld->prices->first()?->price ?? 0;
            
            // Calculate final price
            if ($tld->current_rule) {
                $tld->final_price = $tld->current_rule->applyMarkup($tld->base_price);
            } else {
                $tld->final_price = number_format($tld->base_price, 2, '.', '');
            }
        }

        return $tlds;
    }

    public function editRule($tldId)
    {
        $tld = Tld::findOrFail($tldId);
        $rule = PartnerPricingRule::where('partner_id', currentPartner()->id)
            ->where('tld_id', $tldId)
            ->whereNull('duration')
            ->first();

        $this->editingRules[$tldId] = [
            'markup_type' => $rule ? $rule->markup_type->value : 'percentage',
            'markup_value' => $rule ? $rule->markup_value : '',
        ];

        $this->originalRules[$tldId] = $this->editingRules[$tldId];
    }

    public function saveRule($tldId)
    {
        $this->validate([
            "editingRules.$tldId.markup_type" => 'required|in:fixed,percentage',
            "editingRules.$tldId.markup_value" => 'required|numeric|min:0|max:1000',
        ], [
            "editingRules.$tldId.markup_value.max" => 'Markup value cannot exceed 1000.',
        ]);

        $partner = currentPartner();
        $data = $this->editingRules[$tldId];

        PartnerPricingRule::updateOrCreate(
            [
                'partner_id' => $partner->id,
                'tld_id' => $tldId,
                'duration' => null,
            ],
            [
                'markup_type' => MarkupType::from($data['markup_type']),
                'markup_value' => $data['markup_value'],
                'is_active' => true,
            ]
        );

        unset($this->editingRules[$tldId]);
        unset($this->originalRules[$tldId]);
        
        session()->flash('message', 'Pricing rule saved successfully.');
    }

    public function cancelEdit($tldId)
    {
        unset($this->editingRules[$tldId]);
        unset($this->originalRules[$tldId]);
    }

    public function resetRule($tldId)
    {
        PartnerPricingRule::where('partner_id', currentPartner()->id)
            ->where('tld_id', $tldId)
            ->whereNull('duration')
            ->delete();

        unset($this->editingRules[$tldId]);
        unset($this->originalRules[$tldId]);
        
        session()->flash('message', 'Pricing rule reset to base price.');
    }

    public function toggleBulkForm()
    {
        $this->showBulkForm = !$this->showBulkForm;
        if (!$this->showBulkForm) {
            $this->reset(['bulkMarkupType', 'bulkMarkupValue', 'selectedTlds']);
        }
    }

    public function applyBulkMarkup()
    {
        $this->validate([
            'bulkMarkupType' => 'required|in:fixed,percentage',
            'bulkMarkupValue' => 'required|numeric|min:0|max:1000',
        ]);

        if (empty($this->selectedTlds)) {
            session()->flash('error', 'Please select at least one TLD.');
            return;
        }

        $partner = currentPartner();
        
        DB::transaction(function () use ($partner) {
            foreach ($this->selectedTlds as $tldId) {
                PartnerPricingRule::updateOrCreate(
                    [
                        'partner_id' => $partner->id,
                        'tld_id' => $tldId,
                        'duration' => null,
                    ],
                    [
                        'markup_type' => MarkupType::from($this->bulkMarkupType),
                        'markup_value' => $this->bulkMarkupValue,
                        'is_active' => true,
                    ]
                );
            }
        });

        $count = count($this->selectedTlds);
        $this->reset(['bulkMarkupType', 'bulkMarkupValue', 'selectedTlds', 'showBulkForm']);
        
        session()->flash('message', "Bulk pricing applied to {$count} TLD(s) successfully.");
    }

    public function applyTemplate($template)
    {
        switch ($template) {
            case 'add_20_percent':
                $this->bulkMarkupType = 'percentage';
                $this->bulkMarkupValue = '20';
                break;
            case 'add_5_dollars':
                $this->bulkMarkupType = 'fixed';
                $this->bulkMarkupValue = '5';
                break;
            case 'premium_50_percent':
                $this->bulkMarkupType = 'percentage';
                $this->bulkMarkupValue = '50';
                break;
        }
        
        // Apply to all TLDs
        $allTlds = Tld::active()->pluck('id')->toArray();
        $this->selectedTlds = $allTlds;
        $this->showBulkForm = true;
    }

    public function clearAllRules()
    {
        $partner = currentPartner();
        $count = PartnerPricingRule::where('partner_id', $partner->id)
            ->whereNull('duration')
            ->delete();

        $this->reset(['editingRules', 'originalRules']);
        session()->flash('message', "Cleared {$count} pricing rule(s) successfully.");
    }

    public function calculatePreview()
    {
        $this->validate([
            'previewDomain' => 'required|string',
        ]);

        // Extract TLD from domain
        $parts = explode('.', $this->previewDomain);
        if (count($parts) < 2) {
            session()->flash('error', 'Please enter a valid domain name.');
            return;
        }

        $extension = end($parts);
        $tld = Tld::where('extension', $extension)->first();

        if (!$tld) {
            session()->flash('error', "TLD .{$extension} not found.");
            return;
        }

        $partner = currentPartner();
        $pricingService = app(PricingService::class);
        
        $this->previewPrices = [
            'tld' => $tld->getFullExtension(),
            'register' => [],
            'renew' => [],
            'transfer' => [],
        ];

        foreach (PriceAction::cases() as $action) {
            for ($years = $tld->min_years; $years <= min($tld->max_years, 3); $years++) {
                $price = $pricingService->calculateFinalPrice($tld, $partner, $action, $years);
                if ($price) {
                    $this->previewPrices[$action->value][$years] = $price;
                }
            }
        }

        $this->showPreview = true;
    }

    public function closePreview()
    {
        $this->reset(['previewDomain', 'showPreview', 'previewPrices']);
    }

    public function render()
    {
        return view('livewire.partner.pricing.pricing-rules', [
            'tlds' => $this->tlds,
        ])->layout('layouts.app');
    }
}
