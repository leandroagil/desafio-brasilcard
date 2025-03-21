<?php

namespace App\Exceptions;

use Exception;

class UserException extends Exception
{
    public static function create(string $message = "Erro ao criar usuário")
    {
        return new self($message, 500);
    }

    public static function delete(string $message = "Erro ao remover usuário")
    {
        return new self($message, 500);
    }
}
