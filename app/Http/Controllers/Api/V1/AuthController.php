<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Exceptions\AuthException;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Registrar
     * 
     * @response array{success: boolean, message: string, data: array{user: \App\Http\Resources\V1\UserResource}}
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $data = $this->authService->registerUser($request->all());
            return $this->response('User registered successfully', 201, $data);
        } catch (ValidationException $e) {
            return $this->error('Dados inválidos', 422, ['errors' => $e->errors()]);
        } catch (AuthException | \Exception $e) {
            return $this->error('Erro ao registrar usuário', $e->getCode(), ['error' => $e->getMessage()]);
        }
    }

    /**
     * Logar
     * 
     * @response array{success: boolean, message: string, data: array{token: string, token_type: string, expires_at: string, user: \App\Http\Resources\V1\UserResource}}
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $authData = $this->authService->loginUser($request->all());
            return $this->response('Login successful', 200, $authData);
        } catch (ValidationException $e) {
            return $this->error('Dados inválidos', 422, ['errors' => $e->errors()]);
        } catch (AuthException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->error('Error logging in', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * 
     * Sair
     * 
     * @response array{success: boolean, message: string}
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logoutUser($request->user());
            return $this->response('Logout successful', 200);
        } catch (AuthException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->error('Error logging out', 500, ['error' => $e->getMessage()]);
        }
    }
}
