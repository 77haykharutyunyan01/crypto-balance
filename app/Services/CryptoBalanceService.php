<?php

namespace App\Services;

use App\Models\User;
use App\Models\CryptoAccount;
use App\Models\CryptoTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CryptoBalanceService
{
    public function deposit(
        User $user,
        string $currency,
        float $amount,
        string $type = 'deposit',
        string $riskLevel = 'normal',
        array $meta = [],
    ): CryptoTransaction {
        return DB::transaction(function () use ($user, $currency, $amount, $type, $riskLevel, $meta) {
            $account = $this->lockAccount($user, $currency);

            $transaction = new CryptoTransaction([
                'type' => $type,
                'direction' => CryptoTransaction::DIRECTION_CREDIT,
                'amount' => $amount,
                'status' => $riskLevel === 'high'
                    ? CryptoTransaction::STATUS_PENDING
                    : CryptoTransaction::STATUS_COMPLETED,
                'risk_level' => $riskLevel,
                'meta' => $meta,
            ]);

            if ($riskLevel === 'high') {
                $account->locked_balance += $amount;
            } else {
                $account->available_balance += $amount;
            }

            $account->save();
            $account->transactions()->save($transaction);

            return $transaction;
        });
    }

    public function withdraw(
        User $user,
        string $currency,
        float $amount,
        string $type = 'withdrawal',
        string $riskLevel = 'normal',
        array $meta = [],
    ): CryptoTransaction {
        return DB::transaction(function () use ($user, $currency, $amount, $type, $riskLevel, $meta) {
            $account = $this->lockAccount($user, $currency);

            if ($account->available_balance < $amount) {
                throw new \RuntimeException('Insufficient available balance');
            }

            $transaction = new CryptoTransaction([
                'type' => $type,
                'direction' => CryptoTransaction::DIRECTION_DEBIT,
                'amount' => $amount,
                'status' => $riskLevel === 'high'
                    ? CryptoTransaction::STATUS_PENDING
                    : CryptoTransaction::STATUS_COMPLETED,
                'risk_level' => $riskLevel,
                'meta' => $meta,
            ]);

            if ($riskLevel === 'high') {
                $account->available_balance -= $amount;
                $account->locked_balance += $amount;
            } else {
                $account->available_balance -= $amount;
            }

            $account->save();
            $account->transactions()->save($transaction);

            return $transaction;
        });
    }

    public function confirmTransaction(CryptoTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->refresh();

            if ($transaction->status !== CryptoTransaction::STATUS_PENDING) {
                return;
            }

            $account = $this->lockAccountById($transaction->crypto_account_id);

            if ($transaction->risk_level === 'high') {
                if ($transaction->direction === CryptoTransaction::DIRECTION_CREDIT) {
                    $account->locked_balance -= $transaction->amount;
                    $account->available_balance += $transaction->amount;
                } elseif ($transaction->direction === CryptoTransaction::DIRECTION_DEBIT) {
                    $account->locked_balance -= $transaction->amount;
                }

                $account->save();
            }

            $transaction->status = CryptoTransaction::STATUS_COMPLETED;
            $transaction->save();
        });
    }

    public function failTransaction(CryptoTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->refresh();

            if ($transaction->status !== CryptoTransaction::STATUS_PENDING) {
                return;
            }

            $account = $this->lockAccountById($transaction->crypto_account_id);

            if ($transaction->risk_level === 'high') {
                if ($transaction->direction === CryptoTransaction::DIRECTION_CREDIT) {
                    $account->locked_balance -= $transaction->amount;
                } elseif ($transaction->direction === CryptoTransaction::DIRECTION_DEBIT) {
                    $account->locked_balance -= $transaction->amount;
                    $account->available_balance += $transaction->amount;
                }

                $account->save();
            }

            $transaction->status = CryptoTransaction::STATUS_FAILED;
            $transaction->save();
        });
    }

    private function lockAccount(User $user, string $currency): CryptoAccount
    {
        $currency = strtoupper($currency);

        $account = CryptoAccount::firstOrCreate(
            ['user_id' => $user->id, 'currency' => $currency],
            ['available_balance' => 0, 'locked_balance' => 0]
        );

        return CryptoAccount::query()
            ->where('id', $account->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockAccountById(int $accountId): CryptoAccount
    {
        $account = CryptoAccount::query()
            ->where('id', $accountId)
            ->lockForUpdate()
            ->first();

        if (! $account) {
            throw new ModelNotFoundException('Crypto account not found');
        }

        return $account;
    }
}

