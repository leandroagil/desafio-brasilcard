<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class BaseService
{
    const SHOW_DETAILS = false;

    protected function logError(string $message = "Erro inesperado", Exception $error, array $data = [])
    {
        if (self::SHOW_DETAILS) {
            Log::error($message, [
                'error' => $error->getMessage(),
                'trace' => $error->getTraceAsString(),
                'data'  => $data
            ]);

            return;
        }

        Log::error($message, [
            'error' => $error->getMessage(),
            'data'  => $data
        ]);
    }

    protected function logInfo(string $message, array $data = [],)
    {
        Log::info($message, ['data'  => $data]);
    }
}
