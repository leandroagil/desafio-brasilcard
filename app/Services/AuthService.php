<?php

namespace App\Services;

use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;

class AuthService
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function registerUser(array $data): array
    {
        try {
            $user = $this->userService->createUser($data);
            $token = $user->createToken('registration_token');

            return [
                'token' => $token->plainTextToken,
                'user' => new UserResource($user),
                'message' => 'Usuário registrado com sucesso'
            ];
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Erro ao registrar usuário', [
                'email' => $data['email'] ?? null,
                'error' => $e->getMessage()
            ]);

            throw new HttpException(500, 'Erro ao registrar usuário: ' . $e->getMessage());
        }
    }

    public function loginUser(array $data): array
    {
        $validatedData = $this->validateLoginData($data);
        $credentials = [
            'email' => $validatedData['email'],
            'password' => $validatedData['password']
        ];

        try {
            if (!Auth::attempt($credentials)) {
                Log::info('Erro ao logar', ['email' => $validatedData['email']]);
                throw new AuthenticationException('Email ou senha inválidos');
            }

            $user = User::where('email', $validatedData['email'])->firstOrFail();
            $tokenResult = $user->createToken('access_token');

            Log::info('Usuário logado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            return [
                'token' => $tokenResult->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays(config('sanctum.expiration', 1)),
                'user' => new UserResource($user),
            ];
        } catch (ModelNotFoundException $e) {
            Log::error('Usuário não encontrado', ['email' => $validatedData['email']]);
            throw new AuthenticationException('Email ou senha inválidos');
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Erro ao logar', [
                'email' => $validatedData['email'],
                'error' => $e->getMessage()
            ]);
            throw new HttpException(500, 'Erro ao logar: ' . $e->getMessage());
        }
    }

    private function validateLoginData(array $data): array
    {
        $validator = Validator::make($data, [
            'email' => ['required', 'email:rfc', 'exists:users,email'],
            'password' => ['required', 'string'],
            'remember_me' => ['boolean'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
