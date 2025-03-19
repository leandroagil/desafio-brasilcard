<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): ?array
    {
        $authenticatedUser = Auth::user();
        $amount = $this->protectAmount();

        return [
            'id'          => $this->id,
            'amount'      => $amount,
            'description' => $this->description,
            'type'        => $this->type,
            'status'      => $this->status,

            'sender'    => $this->formatUser($this->sender, $authenticatedUser),
            'receiver'  => $this->formatUser($this->receiver, $authenticatedUser),
        ];
    }

    private function formatUser($user, $authenticatedUser)
    {
        if (!$user) {
            return null;
        }

        $isAuthenticatedUser = $user->id === $authenticatedUser->id;

        return [
            'id' => $user->id,
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'fullName' => "{$user->firstName} {$user->lastName}",
            'balance' => $isAuthenticatedUser ? "R$ " . number_format($user->balance, 2, ',', '.') : 'Hidden',
        ];
    }

    private function protectAmount()
    {
        $authenticatedUser = Auth::user();

        if (!$authenticatedUser || ($this->sender_id !== $authenticatedUser->id && $this->receiver_id !== $authenticatedUser->id)) {
            return 'Hidden';
        }

        return "R$ " . number_format($this->amount, 2, ',', '.');
    }
}
