<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;

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
            return response()->json($users);
        } catch (Exception $e) {
            return $this->error('Erro ao buscar usuários', 400, ['error' => $e->getMessage()]);
        }
    }

    public function create()
    {
        return $this->error('Método não disponível', 405);
    }

    public function store(Request $request)
    {
        try {
            $user = $this->userService->createUser($request->all());
            return $this->response('Usuário criado com sucesso!', 201, new UserResource($user));
        } catch (Exception $e) {
            return $this->error('Erro ao criar novo usuário', 400, ['error' => $e->getMessage()]);
        }
    }

    public function show(User $user)
    {
        try {
            $item = new UserResource($user);
            return response()->json($item);
        } catch (Exception $e) {
            return $this->error('Erro ao buscar usuário.', 400, ['error' => $e->getMessage()]);
        }
    }

    public function edit(string $id)
    {
        return $this->error('Método não disponível', 405);
    }

    public function update(Request $request, User $user)
    {
        try {
            $updatedUser = $this->userService->updateUser($user, $request->all());
            return $this->response('Usuário atualizado com sucesso', 200, new UserResource($updatedUser));
        } catch (Exception $e) {
            return $this->error('Erro ao atualizar usuário', 400, ['error' => $e->getMessage()]);
        }
    }

    public function destroy(User $user)
    {
        try {
            $this->userService->deleteUser($user);
            return $this->response('Usuário removido com sucesso', 200);
        } catch (Exception $e) {
            return $this->error('Erro ao remover usuário', 400, ['error' => $e->getMessage()]);
        }
    }
}
