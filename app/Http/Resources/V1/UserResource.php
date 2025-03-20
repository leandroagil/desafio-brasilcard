<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property UserResource $resource
 */
class UserResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'fullName' => "{$this->firstName} {$this->lastName}",
            'email' => $this->email,
            'balance' => $this->protectAmount($this->balance, $this->id, $this->id),
        ]);
    }
}
