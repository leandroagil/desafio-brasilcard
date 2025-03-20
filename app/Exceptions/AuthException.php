<?php

namespace App\Exceptions;

use Exception;

class AuthException extends Exception
{
    public static function register(string $message = "Erro ao registrar usuário")
    {
        return new self($message, 500);
    }

    public static function login(string $message = "Erro ao logar usuário")
    {
        return new self($message, 500);
    }

    public static function invalidCredentials(string $message = "Email ou senha inválidos")
    {
        return new self($message, 401);
    }

    public static function alreadyLoggedIn(string $message = "Usuário já logado")
    {
        return new self($message, 401);
    }

    public static function logout(string $message = "Erro ao deslogar usuário")
    {
        return new self($message, 500);
    }
}
