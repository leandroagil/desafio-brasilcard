<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    const PROTECTED_BALANCE_PLACEHOLDER = "---";

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at'  => $this->formatTimestamp($this->created_at),
            'updated_at'  => $this->formatTimestamp($this->updated_at),
        ];
    }

    protected function formatUser($user, $authenticatedUser)
    {
        if (!$user) {
            return null;
        }

        $isAuthenticatedUser = $authenticatedUser && $user->id === $authenticatedUser->id;
        $balance = $isAuthenticatedUser
            ? $this->formatCurrency($user->balance)
            : self::PROTECTED_BALANCE_PLACEHOLDER;

        return [
            'id' => $user->id,
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'fullName' => "{$user->firstName} {$user->lastName}",
            'balance' => $balance
        ];
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
        return "R$ " . number_format($value, 2, ',', '.');
    }
}
