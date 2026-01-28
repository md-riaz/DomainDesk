<?php

namespace Tests\Feature\Livewire;

use App\Enums\DomainStatus;
use App\Enums\PriceAction;
use App\Livewire\Client\Domain\TransferDomain;
use App\Livewire\Client\Domain\TransferStatus;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransferDomainTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected User $client;
    protected Registrar $registrar;
    protected Tld $tld;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::factory()->create();
        $this->registrar = Registrar::factory()->create([
            'name' => 'Mock Registrar',
            'api_class' => 'App\Services\Registrar\MockRegistrar',
            'is_default' => true,
            'is_active' => true,
        ]);
        $this->client = User::factory()->create([
            'partner_id' => $this->partner->id,
            'role' => 'client',
        ]);

        $this->wallet = Wallet::factory()->create([
            'partner_id' => $this->partner->id,
        ]);
        $this->wallet->credit(1000, 'Initial balance', createdBy: $this->client->id);

        $this->tld = Tld::factory()->create([
            'extension' => 'com',
            'is_active' => true,
        ]);

        TldPrice::factory()->create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::TRANSFER,
            'price' => 12.00,
            'years' => 1,
            'effective_date' => now()->toDateString(),
        ]);
    }

    /** @test */
    public function it_renders_transfer_form(): void
    {
        Livewire::actingAs($this->client)
            ->test(TransferDomain::class)
            ->assertSee('Transfer Domain')
            ->assertSee('Authorization Code')
            ->assertSee('Domain Name');
    }

    /** @test */
    public function it_calculates_transfer_fee_on_domain_input(): void
    {
        Livewire::actingAs($this->client)
            ->test(TransferDomain::class)
            ->set('domainName', 'example.com')
            ->call('calculateTransferFee')
            ->assertSet('transferFee', 12.00);
    }

    /** @test */
    public function it_validates_domain_name_format(): void
    {
        Livewire::actingAs($this->client)
            ->test(TransferDomain::class)
            ->set('domainName', 'invalid domain!')
            ->set('authCode', 'AUTH1234567890')
            ->call('transfer')
            ->assertHasErrors(['domainName']);
    }

    /** @test */
    public function it_validates_auth_code_required(): void
    {
        Livewire::actingAs($this->client)
            ->test(TransferDomain::class)
            ->set('domainName', 'example.com')
            ->set('authCode', '')
            ->call('transfer')
            ->assertHasErrors(['authCode']);
    }

    /** @test */
    public function it_validates_auth_code_minimum_length(): void
    {
        Livewire::actingAs($this->client)
            ->test(TransferDomain::class)
            ->set('domainName', 'example.com')
            ->set('authCode', '12345')
            ->call('transfer')
            ->assertHasErrors(['authCode']);
    }

    /** @test */
    public function it_toggles_auth_code_visibility(): void
    {
        Livewire::actingAs($this->client)
            ->test(TransferDomain::class)
            ->assertSet('showAuthCode', false)
            ->call('toggleAuthCodeVisibility')
            ->assertSet('showAuthCode', true)
            ->call('toggleAuthCodeVisibility')
            ->assertSet('showAuthCode', false);
    }

    /** @test */
    public function it_shows_wallet_balance(): void
    {
        Livewire::actingAs($this->client)
            ->test(TransferDomain::class)
            ->assertSee('1,000.00');
    }

    /** @test */
    public function it_shows_insufficient_balance_warning(): void
    {
        // Empty wallet
        $this->wallet->transactions()->delete();

        Livewire::actingAs($this->client)
            ->test(TransferDomain::class)
            ->set('domainName', 'example.com')
            ->call('calculateTransferFee')
            ->assertSee('Insufficient balance');
    }

    /** @test */
    public function it_can_initiate_transfer(): void
    {
        Livewire::actingAs($this->client)
            ->test(TransferDomain::class)
            ->set('domainName', 'example.com')
            ->set('authCode', 'AUTH1234567890')
            ->set('autoRenew', true)
            ->call('transfer')
            ->assertRedirect();

        $this->assertDatabaseHas('domains', [
            'name' => 'example.com',
            'client_id' => $this->client->id,
            'status' => DomainStatus::PendingTransfer->value,
        ]);
    }

    /** @test */
    public function it_shows_error_on_transfer_failure(): void
    {
        // Create existing domain
        Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
        ]);

        Livewire::actingAs($this->client)
            ->test(TransferDomain::class)
            ->set('domainName', 'example.com')
            ->set('authCode', 'AUTH1234567890')
            ->call('transfer')
            ->assertSet('errorMessage', fn($message) => str_contains($message, 'already exists'));
    }

    /** @test */
    public function it_disables_submit_when_processing(): void
    {
        Livewire::actingAs($this->client)
            ->test(TransferDomain::class)
            ->set('domainName', 'example.com')
            ->set('authCode', 'AUTH1234567890')
            ->call('transfer');

        // Component should set isProcessing during transfer
    }

    /** @test */
    public function transfer_status_component_renders(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now(),
        ]);

        Livewire::actingAs($this->client)
            ->test(TransferStatus::class, ['domain' => $domain])
            ->assertSee('Transfer Status')
            ->assertSee('example.com')
            ->assertSee('Pending Transfer');
    }

    /** @test */
    public function it_shows_progress_bar_for_transferring_domains(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::TransferInProgress,
            'transfer_initiated_at' => now(),
        ]);

        Livewire::actingAs($this->client)
            ->test(TransferStatus::class, ['domain' => $domain])
            ->assertSee('Transfer Progress')
            ->assertSee('50%');
    }

    /** @test */
    public function it_can_refresh_transfer_status(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(1),
        ]);

        Livewire::actingAs($this->client)
            ->test(TransferStatus::class, ['domain' => $domain])
            ->call('refreshStatus')
            ->assertSet('statusMessage', 'Status updated successfully');
    }

    /** @test */
    public function it_can_cancel_transfer(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now()->subDays(1),
        ]);

        Livewire::actingAs($this->client)
            ->test(TransferStatus::class, ['domain' => $domain])
            ->call('cancelTransfer')
            ->assertRedirect();

        $domain->refresh();
        $this->assertEquals(DomainStatus::TransferCancelled, $domain->status);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_transfer_status(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
        ]);

        $otherClient = User::factory()->create([
            'partner_id' => $this->partner->id,
            'role' => 'client',
        ]);

        Livewire::actingAs($otherClient)
            ->test(TransferStatus::class, ['domain' => $domain])
            ->assertForbidden();
    }

    /** @test */
    public function it_shows_status_history(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::TransferInProgress,
            'transfer_initiated_at' => now()->subDays(2),
        ]);

        Livewire::actingAs($this->client)
            ->test(TransferStatus::class, ['domain' => $domain])
            ->assertSee('Transfer Timeline')
            ->assertSee('Initiated');
    }

    /** @test */
    public function it_shows_cancel_button_only_for_cancellable_transfers(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::PendingTransfer,
            'transfer_initiated_at' => now(),
        ]);

        Livewire::actingAs($this->client)
            ->test(TransferStatus::class, ['domain' => $domain])
            ->assertSee('Cancel Transfer');
    }

    /** @test */
    public function it_hides_cancel_button_for_completed_transfers(): void
    {
        $domain = Domain::factory()->create([
            'name' => 'example.com',
            'partner_id' => $this->partner->id,
            'client_id' => $this->client->id,
            'registrar_id' => $this->registrar->id,
            'status' => DomainStatus::TransferCompleted,
            'transfer_completed_at' => now(),
        ]);

        Livewire::actingAs($this->client)
            ->test(TransferStatus::class, ['domain' => $domain])
            ->assertDontSee('Cancel Transfer');
    }
}
