<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class BaseService
{

    protected function logError(string $message = "Erro inesperado", Exception $error, array $data = [])
    {
        Log::error($message, [
            'error' => $error->getMessage(),
            'trace' => $error->getTraceAsString(),
            'data'  => $data
        ]);
    }

    protected function logInfo(string $message, array $data = [],)
    {
        Log::info($message, ['data'  => $data]);
    }
}
