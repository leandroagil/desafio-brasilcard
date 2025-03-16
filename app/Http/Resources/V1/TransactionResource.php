<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $transactionAmountInReais = "R$ " . number_format($this->amount, 2, ',', '.');
        $receiverTotalAmount = 0;
        $senderTotalAmount = 0;

        $receiverFullName = $this->receiver ? $this->receiver->full_name : 'Unknown';
        $senderFullName = $this->sender ? $this->sender->full_name : 'Unknown';

        if ($this->receiver) {
            $receiverTotalAmount = "R$ " . number_format($this->receiver->total_amount, 2, ',', '.');
        }

        if ($this->sender) {
            $senderTotalAmount = "R$ " . number_format($this->sender->total_amount, 2, ',', '.');
        }

        return [
            'sender'    => $this->sender ? [
                'id' => $this->sender->id,
                'firstName' => $this->sender->firstName,
                'lastName' => $this->sender->lastName,
                'fullName' => $senderFullName,
                'email' => $this->sender->email,
                'total_amount' => $senderTotalAmount,
            ] : null,

            'receiver'  => $this->receiver ? [
                'id' => $this->receiver->id,
                'firstName' => $this->receiver->firstName,
                'lastName' => $this->receiver->lastName,
                'fullName' => $receiverFullName,
                'email' => $this->receiver->email,
                'total_amount' => $receiverTotalAmount,
            ] : null,

            'amount'      => $transactionAmountInReais,
            'description' => $this->description,
            'type'        => $this->type,
            'status'      => $this->status,
        ];
    }
}
