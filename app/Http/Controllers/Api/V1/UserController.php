<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\UserException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Services\UserService;
use App\Models\User;

use Illuminate\Http\JsonResponse;

use Dedoc\Scramble\Attributes\PathParameter;

class UserController extends Controller
{
    /**
     * Obter usuários
     *
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\UserResource[]}
     */
    public function index(UserService $userService): JsonResponse
    {
        try {
            $users = $userService->getAllUsers(15);
            return $this->response('Usuários encontrados com sucesso', 200, $users);
        } catch (\Exception $e) {
            return $this->error('Erro inesperado ao processar dados.', 500);
        }
    }

    /**
     * Obter usuário
     *
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\UserResource}
     */
    #[PathParameter('user', description: 'ID do usuário.')]
    public function show(UserService $userService, User $user): JsonResponse
    {
        try {
            return $this->response('Usuário encontrado com sucesso', 200, new UserResource($user));
        } catch (\Exception $e) {
            return $this->error('Erro inesperado ao processar dados.', 500);
        }
    }

    /**
     * Remover
     *
     * @response array{success: boolean, message: string}
     */
    #[PathParameter('user', description: 'ID do usuário.')]
    public function destroy(UserService $userService, User $user): JsonResponse
    {
        try {
            $userService->deleteUser($user);
            return $this->response('Usuário removido com sucesso', 200);
        } catch (UserException $e) {
            return $this->error('Erro ao remover usuário', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Erro inesperado ao processar dados.', 500);
        }
    }
}
