<?php

namespace App\Services;

use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function registerUser(array $data)
    {
        $validator = $this->validateUser($data);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validatedData = $validator->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);

        return User::create($validatedData);
    }

    public function loginUser(array $data)
    {
        $validator = Validator::make($data, [
            'email' => ['required', 'email:rfc'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $credentials = ['email' => $data['email'], 'password' => $data['password']];

        if (!Auth::attempt($credentials)) {
            throw new HttpException(401, 'Email ou senha inválidos');
        }

        $user = User::where('email', $data['email'])->first();
        $token = $user->createToken('access_token');

        return [
            'token' => $token,
            'user' => new UserResource($user),
        ];
    }

    private function validateUser(array $data)
    {
        return Validator::make($data, [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', Rule::unique('users')],
            'password' => ['required', Password::min(8)],
            'balance' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
