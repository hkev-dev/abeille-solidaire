<?php

namespace App\Service\Payment;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentFactory
{
    private array $services = [];

    public function __construct(private ContainerInterface $container)
    {
    }

    public function registerPaymentService(string $method, string $serviceId): void
    {
        $this->services[strtolower($method)] = $serviceId;
    }

    public function getPaymentService(string $method): PaymentServiceInterface
    {
        $method = strtolower($method);
        
        if (!isset($this->services[$method])) {
            throw new InvalidArgumentException(sprintf(
                'Payment method "%s" is not supported. Supported methods are: %s',
                $method,
                implode(', ', array_keys($this->services))
            ));
        }

        $service = $this->container->get($this->services[$method]);
        
        if (!$service instanceof PaymentServiceInterface) {
            throw new InvalidArgumentException(sprintf(
                'Payment service for method "%s" must implement PaymentServiceInterface',
                $method
            ));
        }

        return $service;
    }

    public function getSupportedMethods(): array
    {
        return array_keys($this->services);
    }
}