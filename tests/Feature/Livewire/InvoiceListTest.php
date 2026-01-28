<?php

namespace Tests\Feature\Livewire;

use App\Enums\InvoiceStatus;
use App\Livewire\Client\Invoice\InvoiceList;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceListTest extends TestCase
{
    use RefreshDatabase;

    private Partner $partner;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::factory()->create();
        $this->client = User::factory()->client()->create(['partner_id' => $this->partner->id]);
    }

    public function test_invoice_list_renders_successfully()
    {
        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->assertSuccessful()
            ->assertSee('Invoices');
    }

    public function test_invoice_list_shows_client_invoices()
    {
        $invoice = Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'invoice_number' => 'INV-123',
        ]);

        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->assertSee('INV-123');
    }

    public function test_invoice_list_does_not_show_other_client_invoices()
    {
        $otherClient = User::factory()->client()->create(['partner_id' => $this->partner->id]);
        
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'invoice_number' => 'INV-MY',
        ]);
        
        Invoice::factory()->for($this->partner)->for($otherClient, 'client')->create([
            'invoice_number' => 'INV-OTHER',
        ]);

        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->assertSee('INV-MY')
            ->assertDontSee('INV-OTHER');
    }

    public function test_invoice_list_status_filter_works()
    {
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'invoice_number' => 'INV-PAID',
            'status' => InvoiceStatus::Paid,
        ]);
        
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'invoice_number' => 'INV-ISSUED',
            'status' => InvoiceStatus::Issued,
        ]);

        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->set('statusFilter', 'paid')
            ->assertSee('INV-PAID')
            ->assertDontSee('INV-ISSUED');
    }

    public function test_invoice_list_date_range_filter_works()
    {
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'invoice_number' => 'INV-OLD',
            'issued_at' => now()->subDays(60),
        ]);
        
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'invoice_number' => 'INV-NEW',
            'issued_at' => now()->subDays(5),
        ]);

        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->set('startDate', now()->subDays(10)->format('Y-m-d'))
            ->assertSee('INV-NEW')
            ->assertDontSee('INV-OLD');
    }

    public function test_invoice_list_clear_filters_works()
    {
        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->set('statusFilter', 'paid')
            ->set('startDate', now()->subDays(10)->format('Y-m-d'))
            ->call('clearFilters')
            ->assertSet('statusFilter', 'all')
            ->assertSet('startDate', null)
            ->assertSet('endDate', null);
    }

    public function test_invoice_list_sorting_works()
    {
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'invoice_number' => 'INV-002',
            'total' => 200.00,
        ]);
        
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'invoice_number' => 'INV-001',
            'total' => 100.00,
        ]);

        $component = Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->set('sortBy', 'total')
            ->set('sortDirection', 'asc');

        $invoices = $component->get('getInvoices');
        $this->assertEquals(100.00, $invoices->first()->total);
    }

    public function test_invoice_list_calculates_total_amount()
    {
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create(['total' => 100.00]);
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create(['total' => 200.00]);

        $component = Livewire::actingAs($this->client)
            ->test(InvoiceList::class);

        $this->assertEquals(300.00, $component->get('totalAmount'));
    }

    public function test_invoice_list_pagination_works()
    {
        Invoice::factory()->count(25)->for($this->partner)->for($this->client, 'client')->create();

        $component = Livewire::actingAs($this->client)
            ->test(InvoiceList::class);

        $invoices = $component->get('getInvoices');
        $this->assertEquals(20, $invoices->count());
        $this->assertEquals(25, $invoices->total());
    }

    public function test_invoice_list_shows_empty_state()
    {
        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->assertSee('No invoices found');
    }

    public function test_invoice_list_displays_status_badges()
    {
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'status' => InvoiceStatus::Paid,
        ]);
        
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'status' => InvoiceStatus::Failed,
        ]);

        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->assertSee('Paid')
            ->assertSee('Failed');
    }

    public function test_invoice_list_shows_invoice_items_summary()
    {
        $invoice = Invoice::factory()->for($this->partner)->for($this->client, 'client')->create();
        InvoiceItem::factory()->for($invoice)->create(['description' => 'Domain Registration']);

        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->assertSee('Domain Registration');
    }

    public function test_invoice_list_sort_direction_toggles()
    {
        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->call('sortByColumn', 'total')
            ->assertSet('sortDirection', 'desc')
            ->call('sortByColumn', 'total')
            ->assertSet('sortDirection', 'asc');
    }

    public function test_filters_reset_pagination()
    {
        Invoice::factory()->count(25)->for($this->partner)->for($this->client, 'client')->create();

        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->set('page', 2)
            ->set('statusFilter', 'paid')
            ->assertSet('page', 1);
    }

    public function test_unauthenticated_user_cannot_access_invoice_list()
    {
        $this->get(route('client.invoices.list'))
            ->assertRedirect(route('login'));
    }

    public function test_invoice_list_filters_by_date_range()
    {
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'invoice_number' => 'INV-RANGE',
            'issued_at' => now()->subDays(15),
        ]);

        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->set('startDate', now()->subDays(20)->format('Y-m-d'))
            ->set('endDate', now()->subDays(10)->format('Y-m-d'))
            ->assertSee('INV-RANGE');
    }

    public function test_invoice_list_calculates_filtered_total_amount()
    {
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'status' => InvoiceStatus::Paid,
            'total' => 100.00,
        ]);
        
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'status' => InvoiceStatus::Issued,
            'total' => 200.00,
        ]);

        $component = Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->set('statusFilter', 'paid');

        $this->assertEquals(100.00, $component->get('totalAmount'));
    }

    public function test_invoice_list_displays_formatted_amounts()
    {
        Invoice::factory()->for($this->partner)->for($this->client, 'client')->create([
            'total' => 1234.56,
        ]);

        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->assertSee('1,234.56');
    }

    public function test_query_string_parameters_are_preserved()
    {
        Livewire::actingAs($this->client)
            ->withQueryParams(['statusFilter' => 'paid'])
            ->test(InvoiceList::class)
            ->assertSet('statusFilter', 'paid');
    }

    public function test_invoice_list_shows_multiple_items_indicator()
    {
        $invoice = Invoice::factory()->for($this->partner)->for($this->client, 'client')->create();
        InvoiceItem::factory()->count(3)->for($invoice)->create();

        Livewire::actingAs($this->client)
            ->test(InvoiceList::class)
            ->assertSee('+2 more');
    }
}
