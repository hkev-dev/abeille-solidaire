<?php

namespace App\RemoteEvent;

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
        private readonly LoggerInterface $logger,
        private readonly StripePaymentService $paymentService,
        private readonly UserRepository $userRepository
    ) {
    }

    public function consume(RemoteEvent $event): void
    {
        $payload = $event->getPayload();
        $eventType = $event->getName();

        $this->logger->info('Received Stripe webhook', [
            'type' => $eventType,
            'payload' => $payload
        ]);

        match ($eventType) {
            'payment_intent.succeeded' => $this->handlePaymentSuccess($payload['data']['object']),
            'payment_intent.payment_failed' => $this->handlePaymentFailure($payload['data']['object']),
            default => ['status' => 'ignored', 'type' => $eventType]
        };
    }

    private function handlePaymentSuccess(array $paymentIntent): array
    {
        $metadata = $paymentIntent['metadata'];
        
        if (!isset($metadata['user_id'])) {
            throw new WebhookException('Missing user ID in payment metadata');
        }

        $user = $this->userRepository->find($metadata['user_id']);
        if (!$user) {
            throw new WebhookException('User not found');
        }

        // Use the new payment service structure
        $this->paymentService->handlePaymentSuccess([
            'payment_intent_id' => $paymentIntent['id'],
            'metadata' => $metadata,
            'amount' => $paymentIntent['amount'],
            'currency' => $paymentIntent['currency'],
            'payment_type' => $metadata['payment_type'] ?? 'registration'
        ]);

        return [
            'status' => 'success',
            'type' => 'payment_intent.succeeded',
            'payment_intent' => $paymentIntent['id'],
        ];
    }

    private function handlePaymentFailure(array $paymentIntent): array
    {
        $metadata = $paymentIntent['metadata'];

        if (!isset($metadata['user_id'])) {
            $this->logger->warning('Missing user ID in failed payment metadata', [
                'payment_intent' => $paymentIntent['id']
            ]);
            return [
                'status' => 'ignored',
                'reason' => 'missing_user_id'
            ];
        }

        // Use the new payment service structure
        $this->paymentService->handlePaymentFailure([
            'payment_intent_id' => $paymentIntent['id'],
            'metadata' => $metadata,
            'error' => $paymentIntent['last_payment_error']['message'] ?? 'Payment failed'
        ]);

        return [
            'status' => 'failed',
            'type' => 'payment_intent.payment_failed',
            'payment_intent' => $paymentIntent['id']
        ];
    }
}
