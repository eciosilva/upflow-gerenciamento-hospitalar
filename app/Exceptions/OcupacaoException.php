<?php

namespace App\Exceptions;

use Exception;

class OcupacaoException extends Exception
{
    public function __construct(
        string $message, 
        protected int $httpCode = 422
    ) {
        parent::__construct($message);
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}