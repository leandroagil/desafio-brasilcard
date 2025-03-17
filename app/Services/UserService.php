<?php

namespace App\Services;

use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UserService
{
    public function getAllUsers()
    {
        return UserResource::collection(User::all());
    }

    public function createUser(array $data)
    {
        $validator = Validator::make(
            $data,
            [
                'firstName' => ['required'],
                'lastName'  => ['required'],
                'email'     => ['required', 'email:rfc', 'unique:users'],
                'password'  => ['required', Password::min(8)],
                'balance'   => ['required', 'numeric', 'gt:-1'],
            ]
        );

        if ($validator->fails()) {
            throw new Exception(json_encode($validator->errors()), 422);
        }

        $data['password'] = Hash::make($data['password']);

        return User::create($data);
    }

    public function updateUser(User $user, array $data)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make(
                $data,
                [
                    'firstName' => ['sometimes', 'required'],
                    'lastName'  => ['sometimes', 'required'],
                    'email'     => ['sometimes', 'required', 'email:rfc', Rule::unique('users')->ignore($user->id)],
                    'password'  => ['sometimes', 'required', Password::min(8)],
                    'balance'   => ['sometimes', 'required', 'min:0.01'],
                ]
            );

            if ($validator->fails()) {
                throw new Exception(json_encode($validator->errors()), 422);
            }

            $validatedData = $validator->validated();

            if (isset($validatedData['password'])) {
                $validatedData['password'] = bcrypt($validatedData['password']);
            }

            $user->update($validatedData);

            DB::commit();

            return $user;
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
}
