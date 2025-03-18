<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        try {
            $user = $this->authService->registerUser($request->all());
            Log::info('Usuário registrado com sucesso', ['user' => $user]);
            return $this->response('Usuário registrado com sucesso!', 201, new UserResource($user));
        } catch (ValidationException $e) {
            Log::warning('Erro de validação ao registrar usuário', ['errors' => $e->errors()]);
            return $this->error('Erro de validação', 422, $e->errors());
        } catch (\Exception $e) {
            Log::error('Erro ao registrar usuário', ['error' => $e->getMessage()]);
            return $this->error('Erro ao registrar usuário', 400, ['error' => $e->getMessage()]);
        }
    }

    public function login(Request $request)
    {
        try {
            $authData = $this->authService->loginUser($request->all());
            Log::info('Login realizado com sucesso', ['authData' => $authData]);
            return $this->response('Login realizado com sucesso!', 200, $authData);
        } catch (ValidationException $e) {
            Log::warning('Erro de validação ao fazer login', ['errors' => $e->errors()]);
            return $this->error('Erro de validação', 422, $e->errors());
        } catch (HttpException $e) {
            Log::error('Erro de autenticação', ['error' => $e->getMessage()]);
            return $this->error($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            Log::error('Erro ao fazer login', ['error' => $e->getMessage()]);
            return $this->error('Erro ao fazer login', 400, ['error' => $e->getMessage()]);
        }
    }
}
