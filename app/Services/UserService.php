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
use Exception;

class UserService
{
    public function getAllUsers()
    {
        return UserResource::collection(User::all());
    }

    public function createUser(array $data)
    {
        $validator = $this->validateUserCreate($data);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validatedData = $validator->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);

        return User::create($validatedData);
    }

    public function updateUser(User $user, array $data)
    {
        DB::beginTransaction();

        try {
            $validator = $this->validateUserUpdate($user, $data);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();

            if (isset($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            }

            $user->update($validatedData);

            DB::commit();
            return new UserResource($user);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Erro ao atualizar usuário: ' . $e->getMessage());
        }
    }

    public function deleteUser(User $user)
    {
        DB::beginTransaction();

        try {
            $user->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Erro ao remover usuário: ' . $e->getMessage());
        }
    }

    private function validateUserCreate(array $data)
    {
        return Validator::make($data, [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName'  => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email:rfc', Rule::unique('users')],
            'password'  => ['required', Password::min(8)],
            'balance'   => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function validateUserUpdate(User $user, array $data)
    {
        return Validator::make($data, [
            'firstName' => ['sometimes', 'required', 'string', 'max:255'],
            'lastName'  => ['sometimes', 'required', 'string', 'max:255'],
            'email'     => ['sometimes', 'required', 'email:rfc', Rule::unique('users')->ignore($user->id)],
            'password'  => ['sometimes', 'required', Password::min(8)],
            'balance'   => ['sometimes', 'required', 'numeric', 'min:0'],
        ]);
    }
}
