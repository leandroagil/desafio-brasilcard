<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\UserException;
use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Models\User;

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

    public function index(): JsonResponse
    {
        try {
            $users = $this->userService->getAllUsers(15);
            return $this->response('Users found successfully', 200, $users);
        } catch (\Exception $e) {
            return $this->error('Error fetching users', 500, ['error' => $e->getMessage()]);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);
            return $this->response('User found successfully', 200, $user);
        } catch (UserException $e) {
            return $this->error('User not found', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Error fetching user', 500, ['error' => $e->getMessage()]);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->all());
            return $this->response('User created successfully', 201, $user);
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, ['errors' => $e->errors()]);
        } catch (UserException $e) {
            return $this->error('Error creating user', $e->getCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->error('Unexpected error', 500, ['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, User $user)
    {
        //
    }

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
