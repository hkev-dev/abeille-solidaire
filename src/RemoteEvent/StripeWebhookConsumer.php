<?php

namespace App\RemoteEvent;

use App\Repository\DonationRepository;
use App\Service\Payment\StripePaymentService;
use Psr\Log\LoggerInterface;
use App\Repository\UserRepository;
use App\Exception\WebhookException;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;

#[AsRemoteEventConsumer('stripe')]
final class StripeWebhookConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly LoggerInterface      $logger,
        private readonly StripePaymentService $paymentService,
    ) {
    }

    public function consume(RemoteEvent $event): void
    {

        $payload = $event->getPayload();
        $eventType = $event->getName();

        $this->logger->info('Received Stripe webhook', [
            'type' => $eventType,
            'payload' => $payload,
            'eventType' => $eventType,
        ]);

        match ($eventType) {
            'payment_intent.succeeded' => $this->handlePaymentSuccess($payload['data']['object']),
            'payment_intent.payment_failed' => $this->handlePaymentFailure($payload['data']['object']),
            'invoice.payment_succeeded' => $this->paymentService->handleSubscriptionSuccess($payload['data']['object']),
            default => ['status' => 'ignored', 'type' => $eventType]
        };
    }

    private function handlePaymentSuccess(array $paymentIntent): array
    {
        $metadata = $paymentIntent['metadata'];

        if (!isset($metadata['donation_id']) && !isset($metadata['membership_id'])) {
            throw new WebhookException('Missing Donation ID || MembershipId in payment metadata');
        }

        $paymentType = $metadata['payment_type'] ?? 'registration';

        try {
            // Handle different payment types
            $payableObject = $this->paymentService->handlePaymentSuccess([
                'payment_intent_id' => $paymentIntent['id'],
                'metadata' => $metadata,
                'amount' => $paymentIntent['amount'],
                'currency' => $paymentIntent['currency'],
                'payment_type' => $paymentType
            ]);

            $this->logger->info('Payment processed successfully', [
                'type' => $paymentType,
                'donation_id' => $payableObject->getId(),
                'payment_intent' => $paymentIntent['id']
            ]);

            return [
                'status' => 'success',
                'type' => 'payment_intent.succeeded',
                'payment_intent' => $paymentIntent['id'],
                'payment_type' => $paymentType
            ];
        } catch (\Exception $e) {
            $this->logger->error('Payment processing failed', [
                'error' => $e->getMessage(),
                'payable_object_id' => $metadata['donation_id'] ?? $metadata['membership_id'],
                'payment_intent' => $paymentIntent['id']
            ]);
            throw $e;
        }
    }

    private function handlePaymentFailure(array $paymentIntent): array
    {
        $metadata = $paymentIntent['metadata'];
        $paymentType = $metadata['payment_type'] ?? 'registration';

        if (!isset($metadata['donation_id'])) {
            $this->logger->warning('Missing Donation ID in failed payment metadata', [
                'payment_intent' => $paymentIntent['id'],
                'payment_type' => $paymentType
            ]);
            return [
                'status' => 'ignored',
                'reason' => 'missing_donation_id'
            ];
        }

        try {
            $this->paymentService->handlePaymentFailure([
                'payment_intent_id' => $paymentIntent['id'],
                'metadata' => $metadata,
                'error' => $paymentIntent['last_payment_error']['message'] ?? 'Payment failed',
                'payment_type' => $paymentType
            ]);

            return [
                'status' => 'failed',
                'type' => 'payment_intent.payment_failed',
                'payment_intent' => $paymentIntent['id'],
                'payment_type' => $paymentType
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle payment failure', [
                'error' => $e->getMessage(),
                'payment_intent' => $paymentIntent['id']
            ]);
            throw $e;
        }
    }
}
