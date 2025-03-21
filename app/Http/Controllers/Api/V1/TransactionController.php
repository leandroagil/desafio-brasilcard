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
    public function index(): JsonResponse
    {
        try {
            $transactions = $this->transactionService->getAllTransactions();
            return $this->response("Transações encontradas!", 200, $transactions);
        } catch (\Exception $e) {
            return $this->error('Erro inesperado', 500, ['error' => $e->getMessage()]);
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
    public function store(Request $request): JsonResponse
    {
        try {
            $transaction = $this->transactionService->transfer($request->all());
            return $this->response('Transferência realizada com sucesso!', 201, $transaction);
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, ['errors' => $e->errors()]);
        } catch (TransactionException $e) {
            return $this->error('Erro ao processar transferência.', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Erro inesperado', 500, ['error' => $e->getMessage()]);
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
            return $this->error('Erro inesperado', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Depositar
     * 
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\TransactionResource}
     */
    #[BodyParameter('user_id', description: 'ID do usuário que está realizando o depósito.', type: 'int', example: 1)]
    #[BodyParameter('amount', description: 'Valor a ser depositado.', type: 'float', example: 500.00)]
    public function deposit(Request $request): JsonResponse
    {
        try {
            $deposit = $this->transactionService->deposit($request->all());
            return $this->response('Depósito realizado com sucesso!', 201, $deposit);
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, ['errors' => $e->errors()]);
        } catch (TransactionException $e) {
            return $this->error('Erro ao processar depósito.', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Erro inesperado', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Reverter
     * 
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\TransactionResource}
     */
    #[PathParameter('transaction', description: 'ID da transação a ser revertida.')]
    public function reverse(Transaction $transaction): JsonResponse
    {
        try {
            $reversedTransaction = $this->transactionService->reverse($transaction);
            return $this->response('Transação revertida com sucesso!', 201, $reversedTransaction);
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, ['errors' => $e->errors()]);
        } catch (TransactionException $e) {
            return $this->error('Erro ao reverter transação.', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Erro inesperado', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Remover 
     * 
     * @response array{success: boolean, message: string}
     */
    #[PathParameter('transaction', description: 'ID da transação.')]
    public function destroy(Transaction $transaction): JsonResponse
    {
        try {
            $this->transactionService->destroy($transaction);
            return $this->response('Transação removida com sucesso');
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, ['errors' => $e->errors()]);
        } catch (TransactionException $e) {
            return $this->error('Erro ao remover transação', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Erro inesperado', 500, ['error' => $e->getMessage()]);
        }
    }
}
