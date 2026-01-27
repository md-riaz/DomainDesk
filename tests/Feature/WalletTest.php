<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TransactionType;
use App\Models\Partner;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_can_be_created_for_partner()
    {
        $partner = Partner::factory()->create();
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'partner_id' => $partner->id,
        ]);
    }

    public function test_wallet_balance_is_zero_by_default()
    {
        $wallet = Wallet::factory()->create();

        $this->assertEquals(0.00, $wallet->balance);
    }

    public function test_wallet_balance_increases_with_credit()
    {
        $wallet = Wallet::factory()->create();
        
        $wallet->credit(100.00, 'Test credit');

        $this->assertEquals(100.00, $wallet->fresh()->balance);
    }

    public function test_wallet_balance_decreases_with_debit()
    {
        $wallet = Wallet::factory()->create();
        $wallet->credit(200.00, 'Initial credit');

        $wallet->debit(50.00, 'Test debit');

        $this->assertEquals(150.00, $wallet->fresh()->balance);
    }

    public function test_wallet_balance_increases_with_refund()
    {
        $wallet = Wallet::factory()->create();
        $wallet->credit(100.00, 'Initial credit');
        $wallet->debit(50.00, 'Test debit');

        $wallet->refund(30.00, 'Test refund');

        $this->assertEquals(80.00, $wallet->fresh()->balance);
    }

    public function test_wallet_balance_can_be_adjusted()
    {
        $wallet = Wallet::factory()->create();
        $wallet->credit(100.00, 'Initial credit');

        $wallet->adjust(25.00, 'Positive adjustment');
        $this->assertEquals(125.00, $wallet->fresh()->balance);

        $wallet->adjust(-15.00, 'Negative adjustment');
        $this->assertEquals(110.00, $wallet->fresh()->balance);
    }

    public function test_debit_throws_exception_when_insufficient_balance()
    {
        $wallet = Wallet::factory()->create();
        $wallet->credit(50.00, 'Initial credit');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient wallet balance');

        $wallet->debit(100.00, 'Excessive debit');
    }

    public function test_debit_allows_negative_balance_when_specified()
    {
        $wallet = Wallet::factory()->create();
        $wallet->credit(50.00, 'Initial credit');

        $wallet->debit(100.00, 'Excessive debit', allowNegative: true);

        $this->assertEquals(-50.00, $wallet->fresh()->balance);
    }

    public function test_wallet_transactions_are_recorded_correctly()
    {
        $wallet = Wallet::factory()->create();

        $wallet->credit(100.00, 'Credit transaction');
        $wallet->debit(30.00, 'Debit transaction');
        $wallet->refund(10.00, 'Refund transaction');

        $this->assertCount(3, $wallet->transactions);
        $this->assertEquals(80.00, $wallet->fresh()->balance);
    }

    public function test_wallet_transactions_cannot_be_updated()
    {
        $transaction = WalletTransaction::factory()->credit()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Wallet transactions cannot be updated');

        $transaction->update(['amount' => 999.99]);
    }

    public function test_wallet_transactions_cannot_be_deleted()
    {
        $transaction = WalletTransaction::factory()->credit()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Wallet transactions cannot be deleted');

        $transaction->delete();
    }

    public function test_concurrent_transactions_maintain_integrity()
    {
        $wallet = Wallet::factory()->create();
        $wallet->credit(1000.00, 'Initial balance');

        // Simulate concurrent transactions
        $wallet->debit(100.00, 'Transaction 1');
        $wallet->debit(200.00, 'Transaction 2');
        $wallet->credit(50.00, 'Transaction 3');

        $this->assertEquals(750.00, $wallet->fresh()->balance);
        $this->assertCount(4, $wallet->fresh()->transactions);
    }

    public function test_transaction_with_reference()
    {
        $wallet = Wallet::factory()->create();
        $user = User::factory()->create([
            'role' => Role::SuperAdmin,
        ]);

        $transaction = $wallet->credit(
            100.00,
            'Credit with reference',
            'App\Models\User',
            $user->id,
            $user->id
        );

        $this->assertEquals('App\Models\User', $transaction->reference_type);
        $this->assertEquals($user->id, $transaction->reference_id);
        $this->assertEquals($user->id, $transaction->created_by);
    }

    public function test_wallet_balance_calculation_with_mixed_transactions()
    {
        $wallet = Wallet::factory()->create();

        // Credits: +500
        $wallet->credit(200.00, 'Credit 1');
        $wallet->credit(300.00, 'Credit 2');

        // Debits: -200
        $wallet->debit(100.00, 'Debit 1');
        $wallet->debit(100.00, 'Debit 2');

        // Refund: +50
        $wallet->refund(50.00, 'Refund 1');

        // Adjustment: +25
        $wallet->adjust(25.00, 'Positive adjustment');

        // Total: 500 - 200 + 50 + 25 = 375
        $this->assertEquals(375.00, $wallet->fresh()->balance);
    }

    public function test_transaction_scopes()
    {
        $wallet = Wallet::factory()->create();
        
        $wallet->credit(100.00, 'Credit');
        $wallet->debit(50.00, 'Debit');
        $wallet->refund(20.00, 'Refund');

        $credits = $wallet->transactions()->credits()->get();
        $debits = $wallet->transactions()->debits()->get();
        
        $this->assertCount(2, $credits); // credit + refund
        $this->assertCount(1, $debits);
    }

    public function test_partner_has_wallet_relationship()
    {
        $partner = Partner::factory()->create();
        $wallet = Wallet::factory()->create(['partner_id' => $partner->id]);

        $this->assertTrue($partner->wallet->is($wallet));
    }
}
