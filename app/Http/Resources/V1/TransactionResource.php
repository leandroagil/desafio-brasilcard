<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\BaseResource;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $authenticatedUser = Auth::user();

        return array_merge(parent::toArray($request), [
            'amount'      => $this->protectAmount($this->amount, $this->sender_id, $this->receiver_id),
            'description' => $this->description,
            'type'        => $this->type,
            'status'      => $this->status,
            'sender'      => $this->formatUser($this->sender, $authenticatedUser),
            'receiver'    => $this->formatUser($this->receiver, $authenticatedUser),
        ]);
    }
}
