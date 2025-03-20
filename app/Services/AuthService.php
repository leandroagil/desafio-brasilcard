<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Http\Resources\V1\UserResource;
use App\Models\User;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
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

            Log::info('User registered successfully', [
                'user_id'    => $user->id,
                'email'      => $user->email,
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            return ['user' => new UserResource($user)];
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error registering user', [
                'email' => $data['email'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
                Log::warning('Login attempt failed - Invalid credentials', [
                    'email' => $validatedData['email'],
                    'ip'    => request()->ip()
                ]);

                throw AuthException::invalidCredentials();
            }

            $user->tokens()->delete();
            $tokenResult = $user->createToken(self::AUTH_TOKEN_KEY);

            Log::info('User logged in successfully with new token', [
                'user_id'    => $user->id,
                'email'      => $user->email,
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent()
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
            Log::error('Error during login', [
                'email' => $data['email'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw AuthException::login($e->getMessage());
        }
    }

    public function logoutUser(User $user): bool
    {
        try {
            $user->tokens()->delete();

            Log::info('User logged out successfully', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'ip'      => request()->ip()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error during logout', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
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
