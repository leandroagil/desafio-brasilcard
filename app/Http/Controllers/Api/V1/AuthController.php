<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            return $this->response('Usuário registrado com sucesso!', 201, new UserResource($user));
        } catch (ValidationException $e) {
            return $this->error('Erro de validação', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Erro ao registrar usuário', 400, ['error' => $e->getMessage()]);
        }
    }

    public function login(Request $request)
    {
        try {
            $authData = $this->authService->loginUser($request->all());
            return $this->response('Login realizado com sucesso!', 200, $authData);
        } catch (ValidationException $e) {
            return $this->error('Erro de validação', 422, $e->errors());
        } catch (HttpException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->error('Erro ao fazer login', 400, ['error' => $e->getMessage()]);
        }
    }
}
