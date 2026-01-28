<?php

namespace Tests\Feature\Livewire\Partner;

use App\Enums\DomainStatus;
use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Livewire\Partner\Client\ClientDetail;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientDetailTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected User $partnerUser;
    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $this->partnerUser = User::factory()->create([
            'role' => Role::Partner,
            'partner_id' => $this->partner->id,
        ]);
        $this->client = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        app(\App\Services\PartnerContextService::class)->setPartner($this->partner);
    }

    public function test_partner_can_view_client_detail()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(ClientDetail::class, ['clientId' => $this->client->id])
            ->assertStatus(200)
            ->assertSee($this->client->name)
            ->assertSee($this->client->email);
    }

    public function test_client_detail_shows_overview_tab()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(ClientDetail::class, ['clientId' => $this->client->id])
            ->assertSet('activeTab', 'overview')
            ->assertSee('Client Information')
            ->assertSee($this->client->name);
    }

    public function test_client_detail_shows_domains_tab()
    {
        $this->actingAs($this->partnerUser);

        $domains = Domain::factory()->count(3)->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'status' => DomainStatus::Active,
        ]);

        Livewire::test(ClientDetail::class, ['clientId' => $this->client->id])
            ->call('setActiveTab', 'domains')
            ->assertSee($domains[0]->name)
            ->assertSee($domains[1]->name);
    }

    public function test_client_detail_shows_invoices_tab()
    {
        $this->actingAs($this->partnerUser);

        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'status' => InvoiceStatus::Paid,
            'total' => 100.00,
        ]);

        Livewire::test(ClientDetail::class, ['clientId' => $this->client->id])
            ->call('setActiveTab', 'invoices')
            ->assertSee($invoice->invoice_number)
            ->assertSee('100.00');
    }

    public function test_partner_can_suspend_client()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(ClientDetail::class, ['clientId' => $this->client->id])
            ->call('suspendClient')
            ->assertSessionHas('success')
            ->assertDispatched('client-updated');

        $this->assertSoftDeleted($this->client);
    }

    public function test_partner_can_activate_client()
    {
        $this->actingAs($this->partnerUser);

        $this->client->delete();

        Livewire::test(ClientDetail::class, ['clientId' => $this->client->id])
            ->call('activateClient')
            ->assertSessionHas('success')
            ->assertDispatched('client-updated');

        $this->assertNotSoftDeleted($this->client);
    }

    public function test_partner_can_reset_client_password()
    {
        $this->actingAs($this->partnerUser);

        $originalPassword = $this->client->password;

        Livewire::test(ClientDetail::class, ['clientId' => $this->client->id])
            ->call('resetPassword')
            ->assertSessionHas('success')
            ->assertDispatched('password-reset');

        $this->client->refresh();
        $this->assertNotEquals($originalPassword, $this->client->password);
    }

    public function test_partner_cannot_view_other_partners_client()
    {
        $otherPartner = Partner::factory()->create(['is_active' => true, 'status' => 'active']);
        $otherClient = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $otherPartner->id,
        ]);

        $this->actingAs($this->partnerUser);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(ClientDetail::class, ['clientId' => $otherClient->id]);
    }

    public function test_invoices_tab_shows_total_spent()
    {
        $this->actingAs($this->partnerUser);

        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'status' => InvoiceStatus::Paid,
            'total' => 100.00,
        ]);

        Invoice::factory()->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'status' => InvoiceStatus::Paid,
            'total' => 50.00,
        ]);

        Livewire::test(ClientDetail::class, ['clientId' => $this->client->id])
            ->call('setActiveTab', 'invoices')
            ->assertSee('150.00');
    }

    public function test_domains_tab_shows_domain_status()
    {
        $this->actingAs($this->partnerUser);

        $activeDomain = Domain::factory()->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
            'status' => DomainStatus::Active,
        ]);

        Livewire::test(ClientDetail::class, ['clientId' => $this->client->id])
            ->call('setActiveTab', 'domains')
            ->assertSee('Active');
    }

    public function test_client_detail_shows_member_since()
    {
        $this->actingAs($this->partnerUser);

        Livewire::test(ClientDetail::class, ['clientId' => $this->client->id])
            ->assertSee('Member Since');
    }

    public function test_tab_navigation_resets_pagination()
    {
        $this->actingAs($this->partnerUser);

        Domain::factory()->count(15)->create([
            'client_id' => $this->client->id,
            'partner_id' => $this->partner->id,
        ]);

        $component = Livewire::test(ClientDetail::class, ['clientId' => $this->client->id])
            ->call('setActiveTab', 'domains');

        $component->call('setActiveTab', 'overview');
    }

    public function test_client_cannot_access_client_detail()
    {
        $anotherClient = User::factory()->create([
            'role' => Role::Client,
            'partner_id' => $this->partner->id,
        ]);

        $this->actingAs($anotherClient);

        $this->get(route('partner.clients.show', $this->client->id))
            ->assertStatus(403);
    }
}
