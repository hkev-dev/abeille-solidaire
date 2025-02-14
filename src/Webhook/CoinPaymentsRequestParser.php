<?php

namespace App\Webhook;

use App\Service\Payment\PaymentFactory;
use Exception;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class CoinPaymentsRequestParser extends AbstractRequestParser
{
    public function __construct(private readonly PaymentFactory $paymentFactory)
    {

    }
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            // Add RequestMatchers to fit your needs
        ]);
    }

    /**
     * @throws JsonException|Exception
     */
    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): ?RemoteEvent
    {
        $paymentService = $this->paymentFactory->getPaymentService('coinpayments');
        $signature = $request->headers->get('HMAC');

        if (!$signature){
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Missing signature');
        }

        $payload = $request->request->all();

        // Verify webhook signature
        if (!$paymentService->verifyPaymentCallback($payload, $signature)) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST,'Invalid webhook signature');
        }


        return new RemoteEvent(
            name: 'coinpayments',
            id: $payload["txn_id"],
            payload: $payload,
        );
    }
}