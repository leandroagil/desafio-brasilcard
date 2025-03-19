<?php

namespace App\Exceptions;

use Exception;

class AuthException extends Exception
{
    public static function register()
    {
        return new self("Error registering user", 500);
    }

    public static function login()
    {
        return new self("Error logging in", 500);
    }

    public static function invalidCredentials()
    {
        return new self("Invalid email or password", 401);
    }

    public static function alreadyLoggedIn()
    {
        return new self("Usuário já logado", 401);
    }

    public static function tokenCreation()
    {
        return new self("Error creating authentication token", 500);
    }

    public static function logout()
    {
        return new self("Error logging out", 500);
    }

    public static function unauthorized()
    {
        return new self("Unauthorized access", 403);
    }
}
