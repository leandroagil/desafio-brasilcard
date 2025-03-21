<?php

namespace App\Exceptions;

use Exception;

class TransactionException extends Exception
{
    public static function insufficientFunds(string $message = "Fundos insuficientes para transferência")
    {
        return new self($message, 400);
    }

    public static function negativeReceiverBalance(string $message = "Transferência cancelada devido ao saldo negativo do destinatário")
    {
        return new self($message, 400);
    }

    public static function negativeUserBalance(string $message = "Depósito cancelado devido ao saldo negativo do usuário")
    {
        return new self($message, 400);
    }

    public static function alreadyReversed(string $message = "A transação já foi revertida")
    {
        return new self($message, 400);
    }

    public static function invalidTransfer(string $message = "Dados de transferência inválidos")
    {
        return new self($message, 400);
    }

    public static function invalidDeposit(string $message = "Dados de depósito inválidos")
    {
        return new self($message, 400);
    }

    public static function transactionCreation(string $message = "Erro ao criar a transação")
    {
        return new self($message, 500);
    }

    public static function transactionUpdate(string $message = "Erro ao atualizar a transação")
    {
        return new self($message, 500);
    }

    public static function transactionReversal(string $message = "Erro ao reverter a transação")
    {
        return new self($message, 500);
    }

    public static function notFound(string $message = "Transação não encontrada")
    {
        return new self($message, 404);
    }
}
