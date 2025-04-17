<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class TransactionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return array_merge(
            parent::toArray($request),
            [
                /**
                 * Usuário que enviou a transação.
                 *
                 * @var array
                 */
                'sender' => $this->whenLoaded('sender', fn() => $this->formatUser($this->sender)),

                /**
                 * Usuário que recebeu a transação.
                 *
                 * @var array
                 */
                'receiver' => $this->whenLoaded('receiver', fn() => $this->formatUser($this->receiver)),

                /**
                 * Valor da transação.
                 *
                 * O valor pode ser protegido para ocultação ou anonimização.
                 *
                 * @var string
                 */
                'amount' => $this->protectAmount($this->amount, $this->sender_id, $this->receiver_id),

                /**
                 * Descrição da transação.
                 *
                 * Pode ser nulo caso não tenha sido fornecida uma descrição.
                 *
                 * @var string|null
                 */
                'description' => $this->description,

                /**
                 * Tipo de transação (ex: depósito, saque, transferência).
                 *
                 * @var string
                 */
                'type' => $this->type,

                /**
                 * Status da transação (ex: pendente, concluída, falha).
                 *
                 * @var string
                 */
                'status' => $this->status,
            ]
        );
    }
}
