<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index()
    {
        try {
            $transactions = $this->transactionService->getAllTransactions();
            return $this->response("Transações encontradas!", 200, $transactions);
        } catch (Exception $e) {
            Log::error('Erro ao buscar transações', ['error' => $e->getMessage()]);
            return $this->error('Erro ao buscar transações', 400, ['error' => $e->getMessage()]);
        }
    }

    public function create()
    {
        return $this->error('Método não disponível', 405);
    }

    public function store(Request $request)
    {
        try {
            $transaction = $this->transactionService->store($request->all());
            Log::info('Transferência realizada com sucesso', ['transaction' => $transaction]);
            return $this->response('Transferência realizada com sucesso!', 201, $transaction);
        } catch (ValidationException $e) {
            Log::warning('Erro de validação na transferência', ['errors' => $e->errors()]);
            return $this->error('Erro de validação.', 400, ['errors' => $e->errors()]);
        } catch (Exception $e) {
            Log::error('Erro ao fazer transferência', ['error' => $e->getMessage()]);
            return $this->error('Erro ao fazer transferência', 400, ['error' => $e->getMessage()]);
        }
    }

    public function show(Transaction $transaction)
    {
        Log::info('Transação encontrada', ['transaction' => $transaction]);
        return $this->response('Transação encontrada!', 200, new TransactionResource($transaction));
    }

    public function edit(string $id)
    {
        return $this->error('Método não disponível', 405);
    }

    public function update(Request $request, Transaction $transaction)
    {
        try {
            $updatedTransaction = $this->transactionService->update($transaction, $request->all());
            Log::info('Transação atualizada com sucesso', ['transaction' => $updatedTransaction]);
            return $this->response('Transação atualizada com sucesso', 200, $updatedTransaction);
        } catch (ValidationException $e) {
            Log::warning('Erro de validação ao atualizar transação', ['errors' => $e->errors()]);
            return $this->error('Erro de validação.', 400, ['error' => $e->errors()]);
        } catch (Exception $e) {
            Log::error('Erro ao atualizar transação', ['error' => $e->getMessage()]);
            return $this->error('Erro ao atualizar transação', 400, ['error' => $e->getMessage()]);
        }
    }

    public function destroy(Transaction $transaction)
    {
        try {
            $this->transactionService->destroy($transaction);
            Log::info('Transação removida com sucesso', ['transaction_id' => $transaction->id]);
            return $this->response('Transação removida com sucesso', 204);
        } catch (ValidationException $e) {
            Log::warning('Erro de validação ao remover transação', ['errors' => $e->errors()]);
            return $this->error('Erro de validação.', 400, ['error' => $e->errors()]);
        } catch (Exception $e) {
            Log::error('Erro ao remover transação', ['error' => $e->getMessage()]);
            return $this->error('Erro ao remover transação', 400, ['error' => $e->getMessage()]);
        }
    }

    public function deposit(Request $request)
    {
        try {
            $deposit = $this->transactionService->deposit($request->all());
            Log::info('Depósito realizado com sucesso', ['deposit' => $deposit]);
            return $this->response('Depósito realizado com sucesso!', 201, $deposit);
        } catch (ValidationException $e) {
            Log::warning('Erro de validação no depósito', ['errors' => $e->errors()]);
            return $this->error('Erro de validação.', 400, ['error' => $e->errors()]);
        } catch (Exception $e) {
            Log::error('Erro ao processar depósito', ['error' => $e->getMessage()]);
            return $this->error('Erro ao processar depósito', 400, ['error' => $e->getMessage()]);
        }
    }
}
