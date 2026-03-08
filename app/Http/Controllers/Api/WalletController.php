<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    public function balance(Request $request)
    {
        $wallet = $request->user()->wallet;
        return response()->json([
            'balance' => $wallet->balance
        ]);
    }

    public function topup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|integer|min:1000|max:10000000'
        ], [
            'amount.required' => 'Nominal tidak boleh kosong.',
            'amount.numeric' => 'Nominal harus berupa angka.',
            'amount.integer' => 'Nominal hanya boleh bilangan bulat.',
            'amount.min' => 'Nominal minimal top-up adalah 1000.',
            'amount.max' => 'Nominal melebihi batas maksimum transaksi.',
        ]);

        try {
            DB::beginTransaction();

            $wallet = $request->user()->wallet;

            $wallet->transactions()->create([
                'type' => 'top_up',
                'amount' => $request->amount,
                'reference' => 'Top Up via Application'
            ]);

            $wallet->balance += $request->amount;
            $wallet->save();

            DB::commit();

            return response()->json([
                'message' => 'Top up successful',
                'balance' => $wallet->balance
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Top up failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|integer|min:1000|max:10000000',
            'target' => 'required|string'
        ], [
            'amount.required' => 'Nominal tidak boleh kosong.',
            'amount.numeric' => 'Nominal harus berupa angka.',
            'amount.integer' => 'Nominal hanya boleh bilangan bulat.',
            'amount.min' => 'Nominal minimal transfer adalah 1000.',
            'amount.max' => 'Nominal melebihi batas maksimum transaksi.',
            'target.required' => 'Tujuan transfer tidak boleh kosong.',
        ]);

        try {
            DB::beginTransaction();

            $sender = $request->user();
            $senderWallet = $sender->wallet;

            if ($senderWallet->balance < $request->amount) {
                throw ValidationException::withMessages([
                    'balance' => ['Insufficient balance.']
                ]);
            }

            $receiver = User::where('email', $request->target)
                ->orWhere('phone_number', $request->target)
                ->first();

            if (!$receiver) {
                throw ValidationException::withMessages([
                    'target' => ['Receiver not found.']
                ]);
            }

            if ($sender->id === $receiver->id) {
                throw ValidationException::withMessages([
                    'target' => ['Cannot transfer to yourself.']
                ]);
            }

            $receiverWallet = $receiver->wallet;

            $senderWallet->balance -= $request->amount;
            $senderWallet->save();

            $receiverWallet->balance += $request->amount;
            $receiverWallet->save();

            Transaction::create([
                'wallet_id' => $senderWallet->id,
                'related_wallet_id' => $receiverWallet->id,
                'type' => 'transfer_out',
                'amount' => $request->amount,
                'reference' => 'Transfer to ' . $receiver->name
            ]);

            Transaction::create([
                'wallet_id' => $receiverWallet->id,
                'related_wallet_id' => $senderWallet->id,
                'type' => 'transfer_in',
                'amount' => $request->amount,
                'reference' => 'Transfer from ' . $sender->name
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transfer successful',
                'balance' => $senderWallet->balance
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Transfer failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function transactions(Request $request)
    {
        $wallet = $request->user()->wallet;
        $transactions = $wallet->transactions()
            ->with('relatedWallet.user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'transactions' => $transactions
        ]);
    }

    public function recentTransfers(Request $request)
    {
        $wallet = $request->user()->wallet;

        $recentTransfers = $wallet->transactions()
            ->where('type', 'transfer_out')
            ->orderBy('created_at', 'desc')
            ->get()
            // Group by related wallet ID to ensure uniqueness
            ->groupBy('related_wallet_id')
            // Get the newest transaction for each related wallet
            ->map(function ($group) {
                return $group->first();
            })
            // Take only the top 5 most recent unique recipients
            ->take(5)
            // Load the user details for these related wallets
            ->map(function ($transaction) {
                $relatedUser = $transaction->relatedWallet->user;
                return [
                    'id' => $relatedUser->id,
                    'name' => $relatedUser->name,
                    'email' => $relatedUser->email,
                    'phone_number' => $relatedUser->phone_number,
                    'last_transfer_date' => $transaction->created_at
                ];
            })
            // Reset keys after map and filter
            ->values();

        return response()->json([
            'recent_transfers' => $recentTransfers
        ]);
    }
}
