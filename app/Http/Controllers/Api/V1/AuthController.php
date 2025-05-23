<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Exceptions\AuthException;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

use Dedoc\Scramble\Attributes\BodyParameter;

class AuthController extends Controller
{
    /**
     * Registro
     *
     * @response array{success: boolean, message: string, data: array{user: \App\Http\Resources\V1\UserResource}}
     */
    #[BodyParameter('firstName', description: 'Primeiro nome do usuário.', type: 'string', example: 'João')]
    #[BodyParameter('lastName', description: 'Sobrenome do usuário.', type: 'string', example: 'Silva')]
    #[BodyParameter('email', description: 'Endereço de e-mail do usuário.', type: 'string', example: 'joao.silva@example.com')]
    #[BodyParameter('password', description: 'Senha do usuário.', type: 'string', example: 'SenhaForte@123')]
    public function register(AuthService $authService, Request $request): JsonResponse
    {
        try {
            $data = $authService->registerUser($request->all());
            return $this->response('Usuário registrado com sucesso', 201, $data);
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, $e->errors());
        } catch (AuthException | \Exception $e) {
            return $this->error('Erro inesperado ao processar dados.', 500);
        }
    }

    /**
     * Login
     *
     * @response array{success: boolean, message: string, data: array{token: string, token_type: string, expires_at: string, user: \App\Http\Resources\V1\UserResource}}
     */
    #[BodyParameter('email', description: 'Endereço de e-mail do usuário.', type: 'string', example: 'joao.silva@example.com')]
    #[BodyParameter('password', description: 'Senha do usuário.', type: 'string', example: 'SenhaForte@123')]
    public function login(AuthService $authService, Request $request): JsonResponse
    {
        try {
            $authData = $authService->loginUser($request->all());
            return $this->response('Usuário logado com sucesso', 200, $authData);
        } catch (ValidationException $e) {
            return $this->error('Erro de validação.', 400, $e->errors());
        } catch (AuthException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->error('Erro inesperado ao processar dados.', 500);
        }
    }

    /**
     *
     * Logout
     *
     * @response array{success: boolean, message: string}
     */
    public function logout(AuthService $authService, Request $request): JsonResponse
    {
        try {
            $authService->logoutUser($request->user());
            return $this->response('Deslogado com sucesso', 200);
        } catch (AuthException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->error('Erro inesperado ao processar dados.', 500);
        }
    }
}
