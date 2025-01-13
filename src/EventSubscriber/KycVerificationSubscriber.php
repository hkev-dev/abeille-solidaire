<?php

namespace App\EventSubscriber;

use App\Event\KycVerificationEvent;
use App\Service\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class KycVerificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KycVerificationEvent::SUBMITTED => 'onKycSubmitted',
            KycVerificationEvent::APPROVED => 'onKycApproved',
            KycVerificationEvent::REJECTED => 'onKycRejected',
        ];
    }

    public function onKycSubmitted(KycVerificationEvent $event): void
    {
        try {
            $this->emailService->sendKycSubmissionConfirmation($event->getUser());
            
            $this->logger->info('KYC submission notification sent', [
                'user_id' => $event->getUser()->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to process KYC submission notification', [
                'user_id' => $event->getUser()->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function onKycApproved(KycVerificationEvent $event): void
    {
        try {
            $this->emailService->sendKycApprovalNotification($event->getUser());
            
            $this->logger->info('KYC approval notification sent', [
                'user_id' => $event->getUser()->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to process KYC approval notification', [
                'user_id' => $event->getUser()->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function onKycRejected(KycVerificationEvent $event): void
    {
        try {
            $this->emailService->sendKycRejectionNotification(
                $event->getUser(),
                $event->getReason()
            );
            
            $this->logger->info('KYC rejection notification sent', [
                'user_id' => $event->getUser()->getId(),
                'reason' => $event->getReason()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to process KYC rejection notification', [
                'user_id' => $event->getUser()->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
