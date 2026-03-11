<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CryptoTransaction;
use App\Models\User;
use App\Services\CryptoBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CryptoBalanceController extends Controller
{
    public function __construct(
        private CryptoBalanceService $service,
    ) {}

    public function deposit(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'risk_level' => ['nullable', 'in:normal,high'],
        ]);

        $tx = $this->service->deposit(
            $user,
            $validated['currency'],
            (float) $validated['amount'],
            riskLevel: $validated['risk_level'] ?? 'normal',
        );

        return response()->json([
            'transaction' => $tx->only(['id', 'type', 'direction', 'amount', 'status', 'risk_level']),
            'account' => [
                'currency' => $tx->account->currency,
                'available_balance' => $tx->account->available_balance,
                'locked_balance' => $tx->account->locked_balance,
            ],
        ]);
    }

    public function withdraw(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'risk_level' => ['nullable', 'in:normal,high'],
        ]);

        $tx = $this->service->withdraw(
            $user,
            $validated['currency'],
            (float) $validated['amount'],
            riskLevel: $validated['risk_level'] ?? 'normal',
        );

        return response()->json([
            'transaction' => $tx->only(['id', 'type', 'direction', 'amount', 'status', 'risk_level']),
            'account' => [
                'currency' => $tx->account->currency,
                'available_balance' => $tx->account->available_balance,
                'locked_balance' => $tx->account->locked_balance,
            ],
        ]);
    }

    public function confirm(CryptoTransaction $transaction): JsonResponse
    {
        $this->service->confirmTransaction($transaction);

        $transaction->refresh();

        return response()->json([
            'transaction' => $transaction->only(['id', 'status']),
        ]);
    }

    public function fail(CryptoTransaction $transaction): JsonResponse
    {
        $this->service->failTransaction($transaction);

        $transaction->refresh();

        return response()->json([
            'transaction' => $transaction->only(['id', 'status']),
        ]);
    }
}

