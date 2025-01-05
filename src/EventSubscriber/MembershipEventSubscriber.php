<?php

namespace App\EventSubscriber;

use App\Event\MembershipRenewalEvent;
use App\Service\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MembershipEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EmailService $emailService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            MembershipRenewalEvent::PAYMENT_COMPLETED => 'onMembershipRenewalCompleted',
            MembershipRenewalEvent::PAYMENT_FAILED => 'onMembershipRenewalFailed',
        ];
    }

    public function onMembershipRenewalCompleted(MembershipRenewalEvent $event): void
    {
        $user = $event->getUser();
        $membership = $event->getMembership();

        $this->logger->info('Membership renewal completed', [
            'user_id' => $user->getId(),
            'membership_id' => $membership?->getId(),
            'valid_until' => $membership?->getEndDate()->format('Y-m-d H:i:s')
        ]);

        // Send confirmation email
        $this->emailService->sendMembershipRenewalConfirmation($user, $membership);
    }

    public function onMembershipRenewalFailed(MembershipRenewalEvent $event): void
    {
        $user = $event->getUser();
        $errorMessage = $event->getErrorMessage();

        $this->logger->error('Membership renewal failed', [
            'user_id' => $user->getId(),
            'error' => $errorMessage
        ]);

        // Send failure notification email
        $this->emailService->sendMembershipRenewalFailure($user, $errorMessage ?? 'Unknown error occurred');
    }
}
