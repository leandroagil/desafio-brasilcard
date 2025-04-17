<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return array_merge(
            parent::toArray($request),
            [
                /**
                 * Nome do usuário.
                 *
                 * @var string
                 */
                'firstName' => $this->firstName,

                /**
                 * Sobrenome do usuário.
                 *
                 * @var string
                 */
                'lastName' => $this->lastName,

                /**
                 * Nome completo do usuário.
                 *
                 * @var string
                 */
                'fullName' => "{$this->firstName} {$this->lastName}",

                /**
                 * Endereço de e-mail do usuário.
                 *
                 * @var string
                 */
                'email' => $this->email,

                /**
                 * Saldo do usuário.
                 *
                 * O saldo pode ser protegido para ocultação ou anonimização.
                 *
                 * @var string
                 */
                'balance' => $this->protectAmount($this->balance, $this->id, $this->id),
            ]
        );
    }
}
