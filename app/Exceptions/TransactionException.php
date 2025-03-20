<?php

namespace App\Exceptions;

use Exception;

class TransactionException extends Exception
{
    public static function insufficientFunds(string $message = "Insufficient funds for transfer")
    {
        return new self($message, 400);
    }

    public static function negativeReceiverBalance(string $message = "Transfer canceled due to receiver's negative balance")
    {
        return new self($message, 400);
    }

    public static function negativeUserBalance(string $message = "Deposit canceled due to user's negative balance")
    {
        return new self($message, 400);
    }

    public static function alreadyReversed(string $message = "Transaction has already been reversed")
    {
        return new self($message, 400);
    }

    public static function invalidTransfer(string $message = "Invalid transfer data")
    {
        return new self($message, 400);
    }

    public static function invalidDeposit(string $message = "Invalid deposit data")
    {
        return new self($message, 400);
    }

    public static function transactionCreation(string $message = "Error creating transaction")
    {
        return new self($message, 500);
    }

    public static function transactionUpdate(string $message = "Error updating transaction")
    {
        return new self($message, 500);
    }

    public static function transactionReversal(string $message = "Error reversing transaction")
    {
        return new self($message, 500);
    }

    public static function notFound(string $message = "Transaction not found")
    {
        return new self($message, 404);
    }
}
