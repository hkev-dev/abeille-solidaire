<?php

namespace App\Service\Payment;

use App\Entity\Membership;
use App\Service\MembershipService;
use DateMalformedStringException;
use Exception;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use App\Entity\User;
use App\Entity\Donation;
use Stripe\PaymentIntent;
use App\Service\MatrixService;
use App\Service\DonationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Stripe\StripeClient;

class PaymentService
{
    const PAYMENT_PROVIDER = 'stripe';

    public function __construct(
        EntityManagerInterface $em,
        ParameterBagInterface  $params,
    ) {
        $this->params = $params;
        #Stripe::setApiKey($this->params->get('stripe.secret_key'));
    }

    public function initializeConfig(): StripeClient
    {
        $config = [
            'api_key'        => $this->params->get('stripe.secret_key'),
            'stripe_version' => '2023-10-16',
        ];
        return new StripeClient($config);
    }

    public function checkoutPayment(
        float $amount,
        string $transactionType, 
        string $productName, 
        string $returnUrl, 
        $quantity = 0
    )
    {

        $stripe = self::initializeConfig();

        return $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $productName,
                    ],
                    'unit_amount' => $amount * 100, // Amount in cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $returnUrl,
            'cancel_url' => $returnUrl,
            'metadata' => [
                'transaction_type' => $transactionType,
                'quantity' => $quantity
            ]
        ]);
    }
}
