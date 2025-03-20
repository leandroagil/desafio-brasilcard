<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\TransactionException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use Exception;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Obter transações
     * 
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\TransactionResource[]}
     */
    public function index()
    {
        try {
            $transactions = $this->transactionService->getAllTransactions();
            return $this->response("Transações encontradas!", 200, $transactions);
        } catch (Exception $e) {
            return $this->error('Erro ao buscar transações', 400, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Transferir
     * 
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\TransactionResource}
     */
    public function store(Request $request)
    {
        try {
            $transaction = $this->transactionService->transfer($request->all());
            return $this->response('Transferência realizada com sucesso!', 201, $transaction);
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, ['errors' => $e->errors()]);
        } catch (TransactionException $e) {
            return $this->error('Erro ao processar transferência.', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Erro ao processar transferência.', 400, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Obter transação
     * 
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\TransactionResource}
     */
    public function show(Transaction $transaction)
    {
        return $this->response('Transação encontrada!', 200, new TransactionResource($transaction));
    }

    /**
     * Remover transação
     * 
     * @response array{success: boolean, message: string}
     */
    public function destroy(Transaction $transaction)
    {
        try {
            $this->transactionService->destroy($transaction);
            return $this->response('Transação removida com sucesso');
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, ['error' => $e->errors()]);
        } catch (TransactionException $e) {
            return $this->error('Erro ao remover transação', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Erro ao remover transação', 400, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Depositar
     * 
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\TransactionResource}
     */
    public function deposit(Request $request)
    {
        try {
            $deposit = $this->transactionService->deposit($request->all());
            return $this->response('Depósito realizado com sucesso!', 201, $deposit);
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, ['error' => $e->errors()]);
        } catch (TransactionException $e) {
            return $this->error('Erro ao processar depósito.', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Erro ao processar depósito.', 400, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Reverter
     * 
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\TransactionResource}
     */
    public function reverse(Transaction $transaction)
    {
        try {
            $reversedTransaction = $this->transactionService->reverse($transaction);
            return $this->response('Transação revertida com sucesso!', 201, $reversedTransaction);
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, ['error' => $e->errors()]);
        } catch (TransactionException $e) {
            return $this->error('Erro ao reverter transação.', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Erro ao reverter transação', 400, ['error' => $e->getMessage()]);
        }
    }
}
