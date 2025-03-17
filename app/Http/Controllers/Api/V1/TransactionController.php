<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Exception;

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
            return response()->json($transactions);
        } catch (Exception $e) {
            return $this->error('Erro ao fazer transferência', 400, ['error' => $e->getMessage()]);
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
            return $this->response('Transferência realizada com sucesso!', 201, $transaction);
        } catch (Exception $e) {
            return $this->error('Erro ao fazer transferência', 400, ['error' => $e->getMessage()]);
        }
    }

    public function show(Transaction $transaction)
    {
        return response()->json(new TransactionResource($transaction));
    }

    public function edit(string $id)
    {
        return $this->error('Método não disponível', 405);
    }

    public function update(Request $request, Transaction $transaction)
    {
        try {
            $updatedTransaction = $this->transactionService->update($transaction, $request->all());
            return $this->response('Transação atualizada com sucesso', 200, $updatedTransaction);
        } catch (Exception $e) {
            return $this->error('Erro ao atualizar transação', 400, ['error' => $e->getMessage()]);
        }
    }

    public function destroy(Transaction $transaction)
    {
        try {
            $this->transactionService->destroy($transaction);
            return $this->response('Transação removida com sucesso', 204);
        } catch (Exception $e) {
            return $this->error('Erro ao remover transação', 400, ['error' => $e->getMessage()]);
        }
    }

    public function deposit(Request $request)
    {
        try {
            $deposit = $this->transactionService->deposit($request->all());
            return $this->response('Depósito realizado com sucesso!', 201, $deposit);
        } catch (Exception $e) {
            return $this->error('Erro ao processar depósito', 400, ['error' => $e->getMessage()]);
        }
    }
}
