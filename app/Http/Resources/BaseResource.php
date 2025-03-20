<?php

namespace App\Http\Resources;

use App\Http\Resources\V1\UserResource;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

use Carbon\Carbon;
use Illuminate\Support\Number;

class BaseResource extends JsonResource
{
    const PROTECTED_BALANCE_PLACEHOLDER = "---";

    public function toArray(Request $request): array
    {
        return [
            /**
             * ID do item.
             * 
             * @var int
             */
            'id' => $this->id,

            /**
             * Data de criação do item.
             * 
             * Formato: `d/m/Y H:i:s`.
             * 
             * @var string
             */
            'created_at'  => $this->formatTimestamp($this->created_at),

            /**
             * Data da última atualização do item.
             * 
             * Formato: `d/m/Y H:i:s`.
             * 
             * @var string
             */
            'updated_at'  => $this->formatTimestamp($this->updated_at),
        ];
    }

    protected function formatUser($user)
    {
        if (!$user) {
            return null;
        }

        return new UserResource($user);
    }

    protected function protectAmount($amount, $sender_id = null, $receiver_id = null)
    {
        $authenticatedUser = Auth::user();

        if (
            !$authenticatedUser ||
            (
                $sender_id && $receiver_id &&
                $sender_id !== $authenticatedUser->id &&
                $receiver_id !== $authenticatedUser->id
            )
        ) {
            return self::PROTECTED_BALANCE_PLACEHOLDER;
        }

        return $this->formatCurrency($amount);
    }

    protected function formatTimestamp($timestamp)
    {
        if (!$timestamp) {
            return null;
        }

        return Carbon::parse($timestamp)->format('d/m/Y H:i:s');
    }

    protected function formatCurrency($value)
    {
        return Number::currency($value, in: 'BRL');
    }
}
