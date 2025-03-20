<?php

namespace App\Services;

use App\Exceptions\TransactionException;
use App\Http\Resources\V1\TransactionResource;
use App\Models\Transaction;
use App\Models\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Exception;
use Throwable;

class TransactionService
{
    const TYPE_TRANSFER = 'transfer';
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAW = 'withdraw';
    const TYPE_REVERSE = 'reverse';

    const STATUS_COMPLETED = 'completed';
    const STATUS_REVERSED = 'reversed';

    public function getAllTransactions(int $perPage = 15)
    {
        try {
            $transactions = Transaction::with(['sender', 'receiver'])->latest()->paginate($perPage);;
            return TransactionResource::collection($transactions);
        } catch (Exception $e) {
            Log::error('Error fetching transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception("Error retrieving transactions", 500);
        }
    }

    public function destroy(Transaction $transaction)
    {
        try {
            return DB::transaction(function () use ($transaction) {
                $isTransfer = $transaction->status === self::STATUS_COMPLETED && $transaction->type === self::TYPE_TRANSFER;

                if ($isTransfer) {
                    $this->reverseTransactionBalances($transaction);
                }

                $result = $transaction->delete();

                Log::info('Transaction deleted', [
                    'transaction_id' => $transaction->id
                ]);

                return $result;
            });
        } catch (ModelNotFoundException $e) {
            throw TransactionException::notFound();
        } catch (Exception $e) {
            Log::error('Error deleting transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw TransactionException::transactionUpdate();
        }
    }

    public function transfer(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $validatedData = $this->validateTransferData($data);

                $sender = User::findOrFail($validatedData['sender_id']);
                $receiver = User::findOrFail($validatedData['receiver_id']);

                $this->verifyTransferEligibility($sender, $receiver, $validatedData['amount']);

                $transaction = Transaction::create([
                    'sender_id'   => $validatedData['sender_id'],
                    'receiver_id' => $validatedData['receiver_id'],
                    'amount'      => $validatedData['amount'],
                    'description' => $validatedData['description'],
                    'status'      => self::STATUS_COMPLETED,
                    'type'        => self::TYPE_TRANSFER,
                ]);

                $sender->decrement('balance', $validatedData['amount']);
                $receiver->increment('balance', $validatedData['amount']);

                Log::info('Transfer completed', [
                    'transaction_id' => $transaction->id,
                    'sender_id'      => $sender->id,
                    'receiver_id'    => $receiver->id,
                    'amount'         => $validatedData['amount']
                ]);

                return new TransactionResource($transaction->fresh(['sender', 'receiver']));
            });
        } catch (ValidationException | TransactionException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            Log::error('User not found in transfer', [
                'data'  => $data,
                'error' => $e->getMessage()
            ]);

            throw TransactionException::invalidTransfer();
        } catch (Exception $e) {
            Log::error('Error creating transfer', [
                'data'  => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw TransactionException::transactionCreation();
        }
    }

    public function deposit(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $validatedData = $this->validateDepositData($data);

                $user = User::findOrFail($validatedData['user_id']);
                $userBalanceIsNegative = $user->balance < 0;

                if ($userBalanceIsNegative) {
                    throw TransactionException::negativeUserBalance();
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
        } catch (ValidationException | TransactionException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            Log::error('User not found in deposit', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            throw TransactionException::invalidDeposit();
        } catch (Exception $e) {
            Log::error('Error creating deposit', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw TransactionException::transactionCreation();
        }
    }

    public function withdraw(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $validator = Validator::make($data, [
                    'user_id' => ['required', 'exists:users,id'],
                    'amount' => ['required', 'numeric', 'min:0.01'],
                    'description' => ['sometimes', 'string', 'max:255'],
                ]);

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                $validatedData = $validator->validated();
                $user = User::findOrFail($validatedData['user_id']);
                $fundsAreInsufficients = $user->balance < $validatedData['amount'];

                if ($fundsAreInsufficients) {
                    throw TransactionException::insufficientFunds();
                }

                $withdraw = Transaction::create([
                    'sender_id' => $validatedData['user_id'],
                    'receiver_id' => null,
                    'amount' => $validatedData['amount'],
                    'status' => self::STATUS_COMPLETED,
                    'description' => $validatedData['description'] ?? 'Account withdrawal',
                    'type' => self::TYPE_WITHDRAW,
                ]);

                $user->decrement('balance', $validatedData['amount']);

                Log::info('Withdrawal completed', [
                    'transaction_id' => $withdraw->id,
                    'user_id' => $user->id,
                    'amount' => $validatedData['amount']
                ]);

                return new TransactionResource($withdraw->fresh(['sender']));
            });
        } catch (ValidationException | TransactionException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error creating withdrawal', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw TransactionException::transactionCreation();
        }
    }

    public function reverse(Transaction $transaction)
    {
        try {
            return DB::transaction(function () use ($transaction) {
                if ($transaction->status === self::STATUS_REVERSED) {
                    throw TransactionException::alreadyReversed();
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
                    'status' => self::STATUS_COMPLETED,
                    'description' => 'Transaction reversed; original: ' . $transaction->id,
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
        } catch (TransactionException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            Log::error('User not found in reversal', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            throw TransactionException::transactionReversal();
        } catch (Throwable $e) {
            Log::error('Error reversing transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw TransactionException::transactionReversal();
        }
    }

    private function validateTransferData(array $data)
    {
        $validator = Validator::make(
            $data,
            [
                'sender_id' => ['required', 'exists:users,id'],
                'receiver_id' => ['required', 'exists:users,id', 'different:sender_id'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'description' => ['required', 'string', 'max:255'],
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
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function verifyTransferEligibility(User $sender, User $receiver, float $amount)
    {
        $selectedAmountIsGreaterThanUserBalance = $amount > $sender->balance;
        if ($selectedAmountIsGreaterThanUserBalance) {
            throw TransactionException::insufficientFunds();
        }

        $receiverBalanceIsNegative = $receiver->balance < 0;
        if ($receiverBalanceIsNegative) {
            throw TransactionException::negativeReceiverBalance();
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
