<?php

namespace App\Services;

use App\Http\Resources\V1\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Exception;

class TransactionService
{
    public function getAllTransactions()
    {
        $transactions = TransactionResource::collection(Transaction::with(['sender', 'receiver'])->get());
        return $transactions;
    }

    public function store(array $data)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make(
                $data,
                [
                    'sender_id'   => ['required', 'exists:users,id'],
                    'receiver_id' => ['required', 'exists:users,id', 'different:sender_id'],
                    'amount'      => ['required', 'numeric', 'min:0.01'],
                    'description' => ['required']
                ]
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();

            $sender = User::find($validatedData['sender_id']);
            $receiver = User::find($validatedData['receiver_id']);

            if ($validatedData['amount'] > $sender->balance) {
                throw new Exception("Saldo insuficiente para nova transferência");
            }

            if ($receiver->balance < 0) {
                throw new Exception('Devido ao saldo negativo do recebedor, sua transferência foi cancelada.');
            }

            $transaction = Transaction::create($validatedData);

            $sender->decrement('balance', $validatedData['amount']);
            $receiver->increment('balance', $validatedData['amount']);

            DB::commit();

            return new TransactionResource($transaction);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), 400);
        }
    }

    public function update(Transaction $transaction, array $data)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make(
                $data,
                [
                    'status'      => ['sometimes', 'string', 'in:completed,cancelled'],
                    'description' => ['sometimes', 'string', 'max:255'],
                ]
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();

            if ($transaction->status == 'completed' && ($validatedData['status'] ?? '') == 'cancelled') {
                $sender = User::find($transaction->sender_id);
                $receiver = User::find($transaction->receiver_id);

                $sender->increment('balance', $transaction->amount);
                $receiver->decrement('balance', $transaction->amount);
            }

            $transaction->update($validatedData);

            DB::commit();

            return new TransactionResource($transaction);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), 400);
        }
    }

    public function destroy(Transaction $transaction)
    {
        DB::beginTransaction();

        try {
            if ($transaction->status == 'completed' && $transaction->type === 'transfer') {
                $sender = User::find($transaction->sender_id);
                $receiver = User::find($transaction->receiver_id);

                $sender->increment('balance', $transaction->amount);
                $receiver->decrement('balance', $transaction->amount);
            }

            $transaction->delete();

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), 400);
        }
    }

    public function deposit(array $data)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make(
                $data,
                [
                    'user_id'     => ['required', 'exists:users,id'],
                    'amount'      => ['required', 'numeric', 'min:0.01'],
                    'description' => ['sometimes', 'string', 'max:255'],
                ]
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();
            $user = User::findOrFail($validatedData['user_id']);

            if ($user->balance < 0) {
                throw new Exception('Devido ao saldo negativo, seu depósito foi cancelado.');
            }

            $deposit = Transaction::create([
                'receiver_id' => $validatedData['user_id'],
                'sender_id'   => null,
                'amount'      => $validatedData['amount'],
                'status'      => 'completed',
                'description' => $validatedData['description'] ?? 'Depósito na conta',
                'type'        => 'deposit'
            ]);

            $user->increment('balance', $validatedData['amount']);

            DB::commit();

            return new TransactionResource($deposit);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), 400);
        }
    }

    public function reverse(Transaction $transaction)
    {
        DB::beginTransaction();

        try {
            $sender = null;

            if ($transaction->status === 'reversed') {
                throw new Exception('A transação já sofreu a reversão!');
            }

            if ($transaction->type !== "deposit") {
                $sender = User::findOrFail($transaction->sender_id);
            }

            $receiver = User::findOrFail($transaction->receiver_id);

            if ($sender) {
                $sender->increment('balance', $transaction->amount);
            }

            $receiver->decrement('balance', $transaction->amount);

            $reverseTransaction = Transaction::create([
                'sender_id'   => $receiver->id,
                'receiver_id' => $sender ? $sender->id : null,
                'amount'      => $transaction->amount,
                'status'      => 'reversed',
                'description' => 'Reversão de transação ' . $transaction->id,
                'type'        => 'reverse',
            ]);

            $transaction->update(['status' => 'reversed']);

            DB::commit();

            return new TransactionResource($reverseTransaction);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), 400);
        }
    }
}
