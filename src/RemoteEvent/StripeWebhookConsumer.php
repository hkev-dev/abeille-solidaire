<?php

namespace App\RemoteEvent;

use Psr\Log\LoggerInterface;
use App\Service\DonationService;
use App\Repository\UserRepository;
use App\Exception\WebhookException;
use App\Service\RegistrationPaymentService;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;

#[AsRemoteEventConsumer('stripe')]
final class StripeWebhookConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RegistrationPaymentService $paymentService,
        private readonly DonationService $donationService,
        private readonly UserRepository $userRepository
    ) {
    }

    public function consume(RemoteEvent $event): void
    {
        $payload = $event->getPayload();
        $eventType = $event->getName();

        // Log the payload
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
        $paymentType = $metadata['payment_type'] ?? null;
        $userId = $metadata['user_id'] ?? null;

        if (!$userId) {
            throw new WebhookException('Missing user ID in payment metadata');
        }

        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new WebhookException('User not found');
        }

        if ($paymentType === 'registration') {
            $this->paymentService->handlePaymentSuccess(
                $user,
                'stripe',
                $paymentIntent['id']
            );
        }

        return [
            'status' => 'success',
            'type' => 'payment_intent.succeeded',
            'payment_intent' => $paymentIntent['id']
        ];
    }

    private function handlePaymentFailure(array $paymentIntent): array
    {
        $metadata = $paymentIntent['metadata'];
        $userId = $metadata['user_id'] ?? null;

        if ($userId) {
            $user = $this->userRepository->find($userId);
            if ($user) {
                $this->paymentService->handlePaymentFailure(
                    $user,
                    'stripe',
                    $paymentIntent['last_payment_error'] ? $paymentIntent['last_payment_error']['message'] : 'Payment failed'
                );
            }
        }

        return [
            'status' => 'failed',
            'type' => 'payment_intent.payment_failed',
            'payment_intent' => $paymentIntent['id']
        ];
    }
}
