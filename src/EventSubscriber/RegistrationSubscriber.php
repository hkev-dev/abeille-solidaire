<?php

namespace App\EventSubscriber;

use App\Event\UserRegistrationEvent;
use App\Service\EmailService;
use App\Service\DonationReceiptService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RegistrationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly DonationReceiptService $receiptService,
        private readonly LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserRegistrationEvent::NAME => 'onUserRegistered',
            UserRegistrationEvent::PAYMENT_COMPLETED => 'onPaymentCompleted',
            UserRegistrationEvent::PAYMENT_FAILED => 'onPaymentFailed',
        ];
    }

    public function onUserRegistered(UserRegistrationEvent $event): void
    {
        $user = $event->getUser();

        try {
            // Send welcome email with payment instructions
            $this->emailService->sendWelcomeEmail($user);

            $this->logger->info('Welcome email sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send welcome email', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function onPaymentCompleted(UserRegistrationEvent $event): void
    {
        $user = $event->getUser();
        $donation = $event->getRegistrationDonation();
        $paymentMethod = $event->getPaymentMethod();
        $membership = $event->getMembership();

        try {
            // Send payment confirmation email
            $this->emailService->sendPaymentConfirmation($user, $paymentMethod);

            // Generate and send donation receipt
            if ($donation) {
                $receipt = $this->receiptService->generateReceipt($donation);
                $this->emailService->sendDonationReceipt($user, $receipt);
            }

            // Send membership confirmation
            if ($membership) {
                $this->emailService->sendMembershipConfirmation($user, $membership);
            }

            // Send welcome to community email
            $this->emailService->sendCommunityWelcome($user);

            $this->logger->info('Registration completion emails sent', [
                'user_id' => $user->getId(),
                'payment_method' => $paymentMethod,
                'membership_id' => $membership ? $membership->getId() : null
            ]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send registration completion emails', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function onPaymentFailed(UserRegistrationEvent $event): void
    {
        $user = $event->getUser();
        $errorMessage = $event->getErrorMessage();

        try {
            // Send payment failure notification
            $this->emailService->sendPaymentFailureNotification(
                $user,
                $errorMessage
            );

            $this->logger->warning('Payment failure notification sent', [
                'user_id' => $user->getId(),
                'error' => $errorMessage
            ]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send payment failure notification', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
