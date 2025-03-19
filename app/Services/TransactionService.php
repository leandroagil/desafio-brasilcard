<?php

namespace App\Services;

use App\Http\Resources\V1\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    const TYPE_TRANSFER = 'transfer';
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_REVERSE = 'reverse';

    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REVERSED = 'reversed';

    public function getAllTransactions(int $perPage = 15)
    {
        $transactions = Transaction::with(['sender', 'receiver'])
            ->latest()
            ->paginate($perPage);

        return TransactionResource::collection($transactions);
    }

    public function transfer(array $data)
    {
        return DB::transaction(function () use ($data) {
            $validatedData = $this->validateTransferData($data);

            $sender = User::findOrFail($validatedData['sender_id']);
            $receiver = User::findOrFail($validatedData['receiver_id']);

            $this->verifyTransferEligibility($sender, $receiver, $validatedData['amount']);

            $transaction = Transaction::create([
                'sender_id' => $validatedData['sender_id'],
                'receiver_id' => $validatedData['receiver_id'],
                'amount' => $validatedData['amount'],
                'description' => $validatedData['description'],
                'status' => self::STATUS_COMPLETED,
                'type' => self::TYPE_TRANSFER,
            ]);

            $sender->decrement('balance', $validatedData['amount']);
            $receiver->increment('balance', $validatedData['amount']);

            Log::info('Transfer completed', [
                'transaction_id' => $transaction->id,
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'amount' => $validatedData['amount']
            ]);

            return new TransactionResource($transaction);
        });
    }

    public function update(Transaction $transaction, array $data)
    {
        return DB::transaction(function () use ($transaction, $data) {
            $validatedData = $this->validateUpdateData($data);

            $originalStatus = $transaction->status;
            $newStatus = $validatedData['status'] ?? $originalStatus;

            if ($originalStatus === self::STATUS_COMPLETED && $newStatus === self::STATUS_CANCELLED) {
                $this->reverseTransactionBalances($transaction);
            }

            $transaction->update($validatedData);

            Log::info('Transaction updated', [
                'transaction_id' => $transaction->id,
                'previous_status' => $originalStatus,
                'new_status' => $newStatus
            ]);

            return new TransactionResource($transaction->fresh(['sender', 'receiver']));
        });
    }

    public function destroy(Transaction $transaction)
    {
        return DB::transaction(function () use ($transaction) {
            if (
                $transaction->status === self::STATUS_COMPLETED &&
                $transaction->type === self::TYPE_TRANSFER
            ) {
                $this->reverseTransactionBalances($transaction);
            }

            $transactionId = $transaction->id;
            $result = $transaction->delete();

            Log::info('Transaction deleted', [
                'transaction_id' => $transactionId
            ]);

            return $result;
        });
    }

    public function deposit(array $data)
    {
        return DB::transaction(function () use ($data) {
            $validatedData = $this->validateDepositData($data);

            $user = User::findOrFail($validatedData['user_id']);

            if (isset($validatedData['check_balance']) && $validatedData['check_balance'] && $user->balance < 0) {
                throw new Exception('Deposit canceled due to negative balance.');
            }

            $deposit = Transaction::create([
                'receiver_id' => $validatedData['user_id'],
                'sender_id' => null,
                'amount' => $validatedData['amount'],
                'status' => self::STATUS_COMPLETED,
                'description' => $validatedData['description'] ?? 'Account deposit',
                'type' => self::TYPE_DEPOSIT,
            ]);

            $user->increment('balance', $validatedData['amount']);

            Log::info('Deposit completed', [
                'transaction_id' => $deposit->id,
                'user_id' => $user->id,
                'amount' => $validatedData['amount']
            ]);

            return new TransactionResource($deposit->fresh(['receiver']));
        });
    }

    public function reverse(Transaction $transaction)
    {
        return DB::transaction(function () use ($transaction) {
            if ($transaction->status === self::STATUS_REVERSED) {
                throw new Exception('Transaction has already been reversed!');
            }

            $sender = $transaction->sender_id ? User::findOrFail($transaction->sender_id) : null;
            $receiver = User::findOrFail($transaction->receiver_id);

            if ($sender) {
                $sender->increment('balance', $transaction->amount);
            }

            $receiver->decrement('balance', $transaction->amount);

            $reverseTransaction = Transaction::create([
                'sender_id' => $receiver->id,
                'receiver_id' => $sender ? $sender->id : null,
                'amount' => $transaction->amount,
                'status' => self::STATUS_REVERSED,
                'description' => 'Reversal of transaction ' . $transaction->id,
                'type' => self::TYPE_REVERSE,
            ]);

            $transaction->update(['status' => self::STATUS_REVERSED]);

            Log::info('Transaction reversed', [
                'original_transaction_id' => $transaction->id,
                'reversal_transaction_id' => $reverseTransaction->id,
                'amount' => $transaction->amount
            ]);

            return new TransactionResource($reverseTransaction->fresh(['sender', 'receiver']));
        });
    }

    public function getTransactionStats(?int $userId = null)
    {
        $query = Transaction::query();

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            });
        }

        $totalSent = clone $query;
        $totalSent = $totalSent->where('sender_id', $userId)
            ->where('status', self::STATUS_COMPLETED)
            ->sum('amount');

        $totalReceived = clone $query;
        $totalReceived = $totalReceived->where('receiver_id', $userId)
            ->where('status', self::STATUS_COMPLETED)
            ->sum('amount');

        $transactionCount = $query->count();

        return [
            'total_sent' => $totalSent,
            'total_received' => $totalReceived,
            'net_balance' => $totalReceived - $totalSent,
            'transaction_count' => $transactionCount,
            'last_transaction_date' => $query->max('created_at')
        ];
    }

    private function validateTransferData(array $data)
    {
        $validator = Validator::make(
            $data,
            [
                'sender_id' => ['required', 'exists:users,id'],
                'receiver_id' => ['required', 'exists:users,id', 'different:sender_id'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'description' => ['required', 'string', 'max:255']
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function validateUpdateData(array $data)
    {
        $validator = Validator::make(
            $data,
            [
                'status' => ['sometimes', 'string', 'in:' . implode(',', [
                    self::STATUS_COMPLETED,
                    self::STATUS_CANCELLED,
                ])],
                'description' => ['sometimes', 'string', 'max:255'],
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function validateDepositData(array $data)
    {
        $validator = Validator::make(
            $data,
            [
                'user_id' => ['required', 'exists:users,id'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'description' => ['sometimes', 'string', 'max:255'],
                'check_balance' => ['sometimes', 'boolean']
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function verifyTransferEligibility(User $sender, User $receiver, float $amount)
    {
        if ($amount > $sender->balance) {
            throw new Exception("Insufficient balance for this transfer");
        }

        if ($receiver->balance < 0) {
            throw new Exception('Transfer cancelled due to receiver\'s negative balance.');
        }
    }

    private function reverseTransactionBalances(Transaction $transaction)
    {
        if ($transaction->sender_id) {
            $sender = User::findOrFail($transaction->sender_id);
            $sender->increment('balance', $transaction->amount);
        }

        if ($transaction->receiver_id) {
            $receiver = User::findOrFail($transaction->receiver_id);
            $receiver->decrement('balance', $transaction->amount);
        }
    }
}
