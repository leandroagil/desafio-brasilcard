<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        try {
            $users = $this->userService->getAllUsers();
            Log::info('Usuários encontrados', ['users' => $users]);
            return $this->response('Usuários encontrados!', 200, $users);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar usuários', ['error' => $e->getMessage()]);
            return $this->error('Erro ao buscar usuários', 400, ['error' => $e->getMessage()]);
        }
    }

    public function show(User $user)
    {
        Log::info('Usuário encontrado', ['user' => $user]);
        return $this->response('Usuário encontrado!', 200, new UserResource($user));
    }

    public function update(Request $request, User $user)
    {
        try {
            $updatedUser = $this->userService->updateUser($user, $request->all());
            Log::info('Usuário atualizado com sucesso', ['user' => $updatedUser]);
            return $this->response('Usuário atualizado com sucesso!', 200, new UserResource($updatedUser));
        } catch (ValidationException $e) {
            Log::warning('Erro de validação ao atualizar usuário', ['errors' => $e->errors()]);
            return $this->error('Erro de validação', 422, $e->errors());
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar usuário', ['error' => $e->getMessage()]);
            return $this->error('Erro ao atualizar usuário', 400, ['error' => $e->getMessage()]);
        }
    }

    public function destroy(User $user)
    {
        try {
            $this->userService->deleteUser($user);
            Log::info('Usuário removido com sucesso', ['user_id' => $user->id]);
            return $this->response('Usuário removido com sucesso!', 200);
        } catch (\Exception $e) {
            Log::error('Erro ao remover usuário', ['error' => $e->getMessage()]);
            return $this->error('Erro ao remover usuário', 400, ['error' => $e->getMessage()]);
        }
    }
}
