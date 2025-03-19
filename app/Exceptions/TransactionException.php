<?php

namespace App\Exceptions;

use Exception;

class TransactionException extends Exception
{
    public static function insufficientFunds()
    {
        return new self("Insufficient funds for transfer", 400);
    }

    public static function negativeReceiverBalance()
    {
        return new self("Transfer canceled due to receiver's negative balance", 400);
    }

    public static function negativeUserBalance()
    {
        return new self("Deposit canceled due to user's negative balance", 400);
    }

    public static function alreadyReversed()
    {
        return new self("Transaction has already been reversed", 400);
    }

    public static function invalidTransfer()
    {
        return new self("Invalid transfer data", 400);
    }

    public static function invalidDeposit()
    {
        return new self("Invalid deposit data", 400);
    }

    public static function transactionCreation()
    {
        return new self("Error creating transaction", 500);
    }

    public static function transactionUpdate()
    {
        return new self("Error updating transaction", 500);
    }

    public static function transactionReversal()
    {
        return new self("Error reversing transaction", 500);
    }

    public static function unauthorizedAction()
    {
        return new self("Unauthorized transaction action", 403);
    }

    public static function notFound()
    {
        return new self("Transaction not found", 404);
    }
}
