<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\UserException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Services\UserService;
use App\Models\User;
use Dedoc\Scramble\Attributes\PathParameter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Obter usuários
     * 
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\UserResource[]}
     */
    public function index(): JsonResponse
    {
        try {
            $users = $this->userService->getAllUsers(15);
            return $this->response('Users found successfully', 200, $users);
        } catch (\Exception $e) {
            return $this->error('Error fetching users', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Obter usuário
     * 
     * @response array{success: boolean, message: string, data: \App\Http\Resources\V1\UserResource}
     */
    #[PathParameter('user', description: 'ID do usuário.')]
    public function show(User $user): JsonResponse
    {
        try {
            return $this->response('User found successfully', 200, new UserResource($user));
        } catch (UserException $e) {
            return $this->error('User not found', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Error fetching user', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Remover
     * 
     * @response array{success: boolean, message: string}
     */
    #[PathParameter('user', description: 'ID do usuário.')]
    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user);
            return $this->response('User deleted successfully', 200);
        } catch (UserException $e) {
            return $this->error('Error deleting user', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Unexpected error', 500, ['error' => $e->getMessage()]);
        }
    }
}
