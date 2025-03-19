<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Services\UserService;
use App\Models\User;

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
            return $this->response('Usuários encontrados!', 200, $users);
        } catch (\Exception $e) {
            return $this->error('Erro ao buscar usuários', 400, ['error' => $e->getMessage()]);
        }
    }

    public function show(User $user)
    {
        return $this->response('Usuário encontrado!', 200, new UserResource($user));
    }

    public function update(Request $request, User $user)
    {
        //
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
