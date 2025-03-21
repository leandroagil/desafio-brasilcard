<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Http\Resources\V1\UserResource;
use App\Models\User;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

use Exception;

class AuthService extends BaseService
{
    const AUTH_TOKEN_KEY = 'access_token';

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function registerUser(array $data): array
    {
        try {
            $user = $this->userService->createUser($data);

            $this->logInfo('Usuário registrado com sucesso', [
                'user_id'    => $user->id,
                'email'      => $user->email
            ]);

            return ['user' => new UserResource($user)];
        } catch (ValidationException $e) {
            $this->logError('Erro ao validar registro de usuário', $e);
            throw $e;
        } catch (\Exception $e) {
            $this->logError('Erro ao registrar usuário', $e, ['email' => $data['email'] ?? null]);
            throw AuthException::register($e->getMessage());
        }
    }

    public function loginUser(array $data): array
    {
        try {
            $validatedData = $this->validateLoginData($data);
            $user = User::where('email', $validatedData['email'])->first();

            if (!$user || !Auth::validate([
                'email'    => $validatedData['email'],
                'password' => $validatedData['password']
            ])) {
                $this->logError('Erro ao logar - credenciais inválidas', new Exception('Credenciais inválidas'), [
                    'email' => $validatedData['email']
                ]);

                throw AuthException::invalidCredentials();
            }

            $user->tokens()->delete();
            $tokenResult = $user->createToken(self::AUTH_TOKEN_KEY);

            $this->logInfo('Usuário logado com sucesso', [
                'user_id'    => $user->id,
                'email'      => $user->email
            ]);

            return [
                'token'      => $tokenResult->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays(config('sanctum.expiration', 1)),
                'user'       => new UserResource($user),
            ];
        } catch (AuthException | ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logError('Erro durante login', $e, [
                'email' => $validatedData['email'],
            ]);

            throw AuthException::login($e->getMessage());
        }
    }

    public function logoutUser(User $user): bool
    {
        try {
            $user->tokens()->delete();

            $this->logInfo('Usuário deslogado com sucesso' . [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logError('Erro ao deslogar usuário', $e, [
                'user_id' => $user->id,
            ]);

            throw AuthException::logout($e->getMessage());
        }
    }

    private function validateLoginData(array $data): array
    {
        $validator = Validator::make($data, [
            'email'       => ['required', 'email:rfc,dns', 'exists:users,email'],
            'password'    => ['required', 'string'],
            'remember_me' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
