<?php

namespace App\Exception;

class UserAccessException extends \RuntimeException
{
    private array $context;
    private string $errorCode;

    public function __construct(
        string $errorCode,
        string $message,
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
