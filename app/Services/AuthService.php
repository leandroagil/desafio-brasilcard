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
                'message' => 'User registered successfully'
            ];
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'email' => $data['email'] ?? null,
                'error' => $e->getMessage()
            ]);

            throw new HttpException(500, 'Registration failed: ' . $e->getMessage());
        }
    }

    public function loginUser(array $data): array
    {
        $validatedData = $this->validateLoginData($data);

        try {
            if (!Auth::attempt([
                'email' => $validatedData['email'],
                'password' => $validatedData['password']
            ])) {
                Log::info('Failed login attempt', ['email' => $validatedData['email']]);
                throw new AuthenticationException('Invalid email or password');
            }

            $user = User::where('email', $validatedData['email'])->firstOrFail();
            $tokenResult = $user->createToken('access_token');

            $this->logSuccessfulLogin($user);

            return [
                'token' => $tokenResult->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays(config('sanctum.expiration', 1)),
                'user' => new UserResource($user),
            ];
        } catch (ModelNotFoundException $e) {
            Log::error('User not found during login', ['email' => $validatedData['email']]);
            throw new AuthenticationException('Invalid email or password');
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'email' => $validatedData['email'],
                'error' => $e->getMessage()
            ]);
            throw new HttpException(500, 'Login failed: ' . $e->getMessage());
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

    private function logSuccessfulLogin(User $user): void
    {
        Log::info('User logged in successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
}
