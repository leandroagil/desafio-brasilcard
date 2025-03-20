<?php

namespace App\Exceptions;

use Exception;

class UserException extends Exception
{
    public static function create(string $message = "Error creating user")
    {
        return new self($message, 500);
    }

    public static function update(string $message = "Error updating user")
    {
        return new self($message, 500);
    }

    public static function delete(string $message = "Error deleting user")
    {
        return new self($message, 500);
    }

    public static function notFound(string $message = "User not found")
    {
        return new self($message, 404);
    }
}
