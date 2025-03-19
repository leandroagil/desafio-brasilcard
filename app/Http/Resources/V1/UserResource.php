<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fullName = "{$this->firstName} {$this->lastName}";

        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'fullName' => $fullName,
            'email' => $this->email,
            'balance' => $this->protectBalance(),
        ];
    }

    private function protectBalance()
    {
        $authenticatedUser = Auth::user();

        if (!$authenticatedUser || $this->id !== $authenticatedUser->id) {
            return 'Hidden';
        }

        return "R$ " . number_format($this->balance, 2, ',', '.');
    }
}
