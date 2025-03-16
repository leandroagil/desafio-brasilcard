<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
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
        $amountInReais = "R$ " . number_format($this->total_amount, 2, ',', '.');
        $fullName = "{$this->firstName} {$this->lastName}";

        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'fullName' => $fullName,
            'email' => $this->email,
            'password' => $this->password,
            'total_amount' => $amountInReais,
        ];
    }
}
