<?php

namespace App\Services;

use App\Http\Resources\V1\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionService
{
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
                throw new Exception(json_encode($validator->errors()), 422);
            }

            $validatedData = $validator->validated();
            $sender = User::find($validatedData['sender_id']);
            $receiver = User::find($validatedData['receiver_id']);

            if ($validatedData['amount'] > $sender->total_amount) {
                throw new Exception("Saldo insuficiente para nova transferência");
            }

            if ($receiver->total_amount < 0) {
                throw new Exception('Devido ao saldo negativo do recebedor, sua transferência foi cancelada.');
            }

            $transaction = Transaction::create($validatedData);

            $sender->decrement('total_amount', $validatedData['amount']);
            $receiver->increment('total_amount', $validatedData['amount']);

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
                    'status' => ['sometimes', 'string', 'in:completed,cancelled'],
                    'description' => ['sometimes', 'string', 'max:255'],
                ]
            );

            if ($validator->fails()) {
                throw new Exception(json_encode($validator->errors()), 422);
            }

            $validatedData = $validator->validated();

            if ($transaction->status == 'completed' && ($validatedData['status'] ?? '') == 'cancelled') {
                $sender = User::find($transaction->sender_id);
                $receiver = User::find($transaction->receiver_id);

                $sender->increment('total_amount', $transaction->amount);
                $receiver->decrement('total_amount', $transaction->amount);
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

                $sender->increment('total_amount', $transaction->amount);
                $receiver->decrement('total_amount', $transaction->amount);
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
                    'user_id' => ['required', 'exists:users,id'],
                    'amount' => ['required', 'numeric', 'min:0.01'],
                    'description' => ['sometimes', 'string', 'max:255'],
                ]
            );

            if ($validator->fails()) {
                throw new Exception(json_encode($validator->errors()), 422);
            }

            $validatedData = $validator->validated();
            $user = User::findOrFail($validatedData['user_id']);

            if ($user->total_amount < 0) {
                throw new Exception('Devido ao saldo negativo, seu depósito foi cancelado.');
            }

            $deposit = Transaction::create([
                'receiver_id' => $validatedData['user_id'],
                'sender_id' => null,
                'amount' => $validatedData['amount'],
                'status' => 'completed',
                'description' => $validatedData['description'] ?? 'Depósito na conta',
                'type' => 'deposit'
            ]);

            $user->increment('total_amount', $validatedData['amount']);

            DB::commit();

            return new TransactionResource($deposit);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), 400);
        }
    }
}
