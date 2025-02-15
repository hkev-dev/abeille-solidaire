<?php

namespace App\RemoteEvent;

use App\Exception\WebhookException;
use App\Service\Payment\CoinPaymentsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer('coinpayments')]
final class CoinPaymentsWebhookConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly LoggerInterface      $logger,
        private readonly CoinPaymentsService  $paymentService,
    ) {
    }

    public function consume(RemoteEvent $event): void
    {

        $payload = $event->getPayload();
        $eventType = $event->getName();

        $this->logger->info('Received CoinPayments webhook', [
            'type' => $eventType,
            'payload' => $payload
        ]);

        try {

        if ($payload["status"] === '100') {
//        if ($payload["status"] === '100' || $payload["status"] === '0') {
            $this->handlePaymentSuccess($payload);
        } elseif ((int) $payload["status"] < 0) {
            $this->handlePaymentFailure($payload);
        } else {
            $this->logger->info('Ignoring CoinPayments webhook', [
                'type' => $eventType,
                'payload' => $payload,
                'status' => $payload["status"]
            ]);
        }
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle CoinPayments webhook', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
        }
    }

    private function handlePaymentSuccess(array $payload): void
    {
        $metadata = json_decode($payload['custom'], true);

        if (!isset($metadata['donation_id']) && !isset($metadata['membership_id'])) {
            throw new WebhookException('Missing Donation ID || MembershipId in payment metadata');
        }

        $paymentType = $metadata['payment_type'] ?? 'registration';

        try {
            // Handle different payment types
            $payableObject = $this->paymentService->handlePaymentSuccess($payload);

            $this->logger->info('Payment processed successfully', [
                'type' => $paymentType,
                'donation_id' => $payableObject->getId(),
                'payment_intent' => $payload['txn_id']
            ]);

            return;
        } catch (\Exception $e) {
            $this->logger->error('Payment processing failed', [
                'error' => $e->getMessage(),
                'payable_object_id' => $metadata['donation_id'] ?? $metadata['membership_id'],
                'payment_intent' => $payload['txn_id']
            ]);
            throw $e;
        }
    }

    private function handlePaymentFailure(array $payload): void
    {
        $metadata = json_decode($payload['custom'], true);
        $paymentType = $metadata['payment_type'] ?? 'registration';

        if (!isset($metadata['donation_id'])) {
            $this->logger->warning('Missing Donation ID in failed payment metadata', [
                'payment_intent' => $payload['txn_id'],
                'payment_type' => $paymentType
            ]);
            return;
        }

        try {
            $this->paymentService->handlePaymentFailure([
                'payment_intent_id' => $payload['txn_id'],
                'metadata' => $metadata,
                'error' => $payload['last_payment_error']['message'] ?? 'Payment failed',
                'payment_type' => $paymentType
            ]);

            return;
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle payment failure', [
                'error' => $e->getMessage(),
                'payment_intent' => $payload['txn_id']
            ]);
            throw $e;
        }
    }
}
