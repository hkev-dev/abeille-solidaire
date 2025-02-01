<?php

namespace App\Exception;

class PaymentProcessingException extends \RuntimeException
{
    private ?string $paymentMethod;
    private ?string $transactionId;

    public function __construct(
        ?string $message = "",
        ?string $paymentMethod = null,
        ?string $transactionId = null,
        ?int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->paymentMethod = $paymentMethod;
        $this->transactionId = $transactionId;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }
}
