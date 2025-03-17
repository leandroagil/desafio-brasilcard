<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            return $this->response('Usuários encontrados!', 200, $users);
        } catch (\Exception $e) {
            return $this->error('Erro ao buscar usuários', 400, ['error' => $e->getMessage()]);
        }
    }

    public function show(User $user)
    {
        try {
            return $this->response('Usuário encontrado!', 200, new UserResource($user));
        } catch (\Exception $e) {
            return $this->error('Erro ao buscar usuário', 400, ['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            $updatedUser = $this->userService->updateUser($user, $request->all());
            return $this->response('Usuário atualizado com sucesso!', 200, new UserResource($updatedUser));
        } catch (ValidationException $e) {
            return $this->error('Erro de validação', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Erro ao atualizar usuário', 400, ['error' => $e->getMessage()]);
        }
    }

    public function destroy(User $user)
    {
        try {
            $this->userService->deleteUser($user);
            return $this->response('Usuário removido com sucesso!', 200);
        } catch (\Exception $e) {
            return $this->error('Erro ao remover usuário', 400, ['error' => $e->getMessage()]);
        }
    }
}
