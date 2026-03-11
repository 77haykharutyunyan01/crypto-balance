<?php

namespace Tests\Feature;

use App\Models\CryptoAccount;
use App\Models\CryptoTransaction;
use App\Models\User;
use App\Services\CryptoBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CryptoBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_deposit_increases_available_balance(): void
    {
        $user = User::factory()->create();

        $service = $this->app->make(CryptoBalanceService::class);

        $service->deposit($user, 'BTC', 0.5);

        $account = CryptoAccount::where('user_id', $user->id)
            ->where('currency', 'BTC')
            ->firstOrFail();

        $this->assertEquals(0.5, (float) $account->available_balance);
        $this->assertEquals(0.0, (float) $account->locked_balance);

        $this->assertDatabaseHas('crypto_transactions', [
            'crypto_account_id' => $account->id,
            'type' => 'deposit',
            'direction' => CryptoTransaction::DIRECTION_CREDIT,
            'status' => CryptoTransaction::STATUS_COMPLETED,
        ]);
    }

    public function test_high_risk_deposit_goes_to_locked_balance_until_confirmed(): void
    {
        $user = User::factory()->create();
        $service = $this->app->make(CryptoBalanceService::class);

        $tx = $service->deposit($user, 'ETH', 1.0, riskLevel: 'high');

        $account = $tx->account()->first();

        $this->assertEquals(0.0, (float) $account->available_balance);
        $this->assertEquals(1.0, (float) $account->locked_balance);
        $this->assertEquals(CryptoTransaction::STATUS_PENDING, $tx->status);

        $service->confirmTransaction($tx);
        $account->refresh();
        $tx->refresh();

        $this->assertEquals(1.0, (float) $account->available_balance);
        $this->assertEquals(0.0, (float) $account->locked_balance);
        $this->assertEquals(CryptoTransaction::STATUS_COMPLETED, $tx->status);
    }

    public function test_withdraw_fails_when_balance_is_insufficient(): void
    {
        $user = User::factory()->create();
        $service = $this->app->make(CryptoBalanceService::class);

        $this->expectException(\RuntimeException::class);

        $service->withdraw($user, 'BTC', 1.0);
    }

    public function test_high_risk_withdraw_moves_funds_to_locked_and_on_fail_returns_them(): void
    {
        $user = User::factory()->create();
        $service = $this->app->make(CryptoBalanceService::class);

        $service->deposit($user, 'USDT', 100.0);
        $account = CryptoAccount::where('user_id', $user->id)
            ->where('currency', 'USDT')
            ->firstOrFail();

        $this->assertEquals(100.0, (float) $account->available_balance);
        $this->assertEquals(0.0, (float) $account->locked_balance);

        $tx = $service->withdraw($user, 'USDT', 40.0, riskLevel: 'high');
        $account->refresh();

        $this->assertEquals(60.0, (float) $account->available_balance);
        $this->assertEquals(40.0, (float) $account->locked_balance);
        $this->assertEquals(CryptoTransaction::STATUS_PENDING, $tx->status);

        $service->failTransaction($tx);
        $account->refresh();
        $tx->refresh();

        $this->assertEquals(100.0, (float) $account->available_balance);
        $this->assertEquals(0.0, (float) $account->locked_balance);
        $this->assertEquals(CryptoTransaction::STATUS_FAILED, $tx->status);
    }
}

