<?php

namespace App\Services;

use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
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
        $validatedData['password'] = Hash::make($validatedData['password']);

        return DB::transaction(function () use ($validatedData) {
            return User::create($validatedData);
        });
    }

    public function updateUser(User $user, array $data)
    {
        try {
            return DB::transaction(function () use ($user, $data) {
                $validatedData = $this->validateUserData($data, 'update', $user);

                if (isset($validatedData['password'])) {
                    $validatedData['password'] = Hash::make($validatedData['password']);
                }

                $user->update($validatedData);
                $user->refresh();

                return new UserResource($user);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('User update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to update user: ' . $e->getMessage());
        }
    }

    public function deleteUser(User $user)
    {
        try {
            return DB::transaction(function () use ($user) {
                return $user->delete();
            });
        } catch (Exception $e) {
            Log::error('User deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception('Failed to delete user: ' . $e->getMessage());
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
