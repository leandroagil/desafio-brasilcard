<?php

namespace App\Services;

use App\Exceptions\UserException;
use App\Http\Resources\V1\UserResource;
use App\Models\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

use Exception;
use Throwable;

class UserService
{
    public function getAllUsers(int $perPage = 15)
    {
        try {
            return UserResource::collection(User::paginate($perPage));
        } catch (Exception $e) {
            Log::error('Error fetching users', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception("Error retrieving users", 500);
        }
    }

    public function createUser(array $data)
    {
        try {
            $validatedData = $this->validateUserData($data, 'create');

            $user = DB::transaction(function () use ($validatedData) {
                return User::create($validatedData);
            });

            return new UserResource($user);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error creating user', [
                'data'  => array_diff_key($data, array_flip(['password'])),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw UserException::create($e->getMessage());
        }
    }

    public function deleteUser(User $user)
    {
        try {
            $deleted = DB::transaction(function () use ($user) {
                return $user->delete();
            });

            if (!$deleted) {
                throw UserException::delete();
            }

            return true;
        } catch (UserException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error deleting user', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            throw UserException::delete($e->getMessage());
        }
    }

    private function validateUserData(array $data, string $operation, ?User $user = null)
    {
        $rules = [
            'firstName' => ['string', 'max:255'],
            'lastName'  => ['string', 'max:255'],
            'email'     => ['email:rfc,dns'],
            'password'  => [
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
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

            if ($user) {
                $rules['email'][] = Rule::unique('users')->ignore($user->id);
            }
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
