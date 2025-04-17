<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\TransactionException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TransactionResource;
use App\Services\TransactionService;
use App\Models\Transaction;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\PathParameter;

use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    /**
     * Obter transações
     *
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\TransactionResource[]}
     */
    public function index(TransactionService $transactionService): JsonResponse
    {
        try {
            $transactions = $transactionService->getAllTransactions();
            return $this->response("Transações encontradas!", 200, $transactions);
        } catch (\Exception $e) {
            return $this->error('Erro inesperado ao processar dados.', 500);
        }
    }

    /**
     * Transferir
     *
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\TransactionResource}
     */
    #[BodyParameter('sender_id', description: 'ID do usuário que está enviando a transferência.', type: 'int', example: 1)]
    #[BodyParameter('receiver_id', description: 'ID do usuário que está recebendo a transferência.', type: 'int', example: 2)]
    #[BodyParameter('amount', description: 'Valor a ser transferido.', type: 'float', example: 100.50)]
    #[BodyParameter('description', description: 'Descrição opcional da transação.', type: 'string', example: 'Pagamento de serviço')]
    public function store(TransactionService $transactionService, Request $request): JsonResponse
    {
        try {
            $transaction = $transactionService->transfer($request->all());
            return $this->response('Transferência realizada com sucesso!', 201, $transaction);
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, $e->errors());
        } catch (TransactionException $e) {
            return $this->error('Erro ao processar transferência.', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Erro inesperado ao processar dados.', 500);
        }
    }

    /**
     * Obter transação
     *
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\TransactionResource}
     */
    #[PathParameter('transaction', description: 'ID da transação.')]
    public function show(Transaction $transaction): JsonResponse
    {
        try {
            return $this->response('Transação encontrada  com sucesso', 200, new TransactionResource($transaction));
        } catch (\Exception $e) {
            return $this->error('Erro inesperado ao processar dados.', 500);
        }
    }

    /**
     * Depositar
     *
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\TransactionResource}
     */
    #[BodyParameter('user_id', description: 'ID do usuário que está realizando o depósito.', type: 'int', example: 1)]
    #[BodyParameter('amount', description: 'Valor a ser depositado.', type: 'float', example: 500.00)]
    public function deposit(TransactionService $transactionService, Request $request): JsonResponse
    {
        try {
            $deposit = $transactionService->deposit($request->all());
            return $this->response('Depósito realizado com sucesso!', 201, $deposit);
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, $e->errors());
        } catch (TransactionException $e) {
            return $this->error('Erro ao processar depósito.', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Erro inesperado ao processar dados.', 500);
        }
    }

    /**
     * Reverter
     *
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\TransactionResource}
     */
    #[PathParameter('transaction', description: 'ID da transação a ser revertida.')]
    public function reverse(TransactionService $transactionService, Transaction $transaction): JsonResponse
    {
        try {
            $reversedTransaction = $transactionService->reverse($transaction);
            return $this->response('Transação revertida com sucesso!', 201, $reversedTransaction);
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, $e->errors());
        } catch (TransactionException $e) {
            return $this->error('Erro ao reverter transação.', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Erro inesperado ao processar dados.', 500);
        }
    }

    /**
     * Remover
     *
     * @response array{success: boolean, message: string}
     */
    #[PathParameter('transaction', description: 'ID da transação.')]
    public function destroy(TransactionService $transactionService, Transaction $transaction): JsonResponse
    {
        try {
            $transactionService->destroy($transaction);
            return $this->response('Transação removida com sucesso');
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, $e->errors());
        } catch (TransactionException $e) {
            return $this->error('Erro ao remover transação', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Erro inesperado ao processar dados.', 500);
        }
    }
}
