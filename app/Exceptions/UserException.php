<?php

namespace App\Exceptions;

use Exception;

class UserException extends Exception
{
    public static function create()
    {
        return new self("Error creating user", 500);
    }

    public static function update()
    {
        return new self("Error updating user", 500);
    }

    public static function delete()
    {
        return new self("Error deleting user", 500);
    }

    public static function notFound()
    {
        return new self("User not found", 404);
    }
}
