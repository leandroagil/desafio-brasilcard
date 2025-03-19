<?php

namespace App\Services;

use App\Http\Resources\V1\UserResource;
use App\Models\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

use Exception;

class UserService
{
    public function getAllUsers(int $perPage = 15)
    {
        return UserResource::collection(User::paginate($perPage));
    }

    public function createUser(array $data)
    {
        $validatedData = $this->validateUserData($data, 'create');

        return DB::transaction(function () use ($validatedData) {
            return User::create($validatedData);
        });
    }

    public function updateUser(User $user, array $data)
    {
        //
    }

    public function deleteUser(User $user)
    {
        try {
            return DB::transaction(function () use ($user) {
                return $user->delete();
            });
        } catch (Exception $e) {
            Log::error('Erro ao excluir usuário', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception('Erro ao excluir usuário: ' . $e->getMessage());
        }
    }

    private function validateUserData(array $data, string $operation, ?User $user = null)
    {
        $rules = [
            'firstName' => ['string', 'max:255'],
            'lastName'  => ['string', 'max:255'],
            'email'     => ['email:rfc'],
            'password'  => [Password::min(8)->mixedCase()->numbers()],
            'balance'   => ['numeric', 'min:0'],
        ];

        if ($operation === 'create') {
            $rules = array_map(function ($rule) {
                return array_merge(['required'], $rule);
            }, $rules);

            $rules['email'][] = Rule::unique('users');
            $rules['balance'][] = 'sometimes';
        }

        if ($operation === 'update') {
            $rules = array_map(function ($rule) {
                return array_merge(['sometimes'], $rule);
            }, $rules);

            $rules['email'][] = Rule::unique('users')->ignore($user->id);
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
