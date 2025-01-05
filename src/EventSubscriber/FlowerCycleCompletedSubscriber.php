<?php

namespace App\EventSubscriber;

use App\Event\FlowerCycleCompletedEvent;
use App\Service\EmailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class FlowerCycleCompletedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            FlowerCycleCompletedEvent::class => [
                ['sendProgressionNotification', 10],
                ['logProgression', 0]
            ]
        ];
    }

    public function sendProgressionNotification(FlowerCycleCompletedEvent $event): void
    {
        try {
            $this->emailService->sendFlowerProgressionEmail(
                $event->getUser(),
                $event->getNextFlower(),
                $event->getCompletedFlower(),
                $event->getWalletAmount()
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to send flower progression email', [
                'user_id' => $event->getUser()->getId(),
                'completed_flower' => $event->getCompletedFlower()->getName(),
                'next_flower' => $event->getNextFlower()?->getName(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function logProgression(FlowerCycleCompletedEvent $event): void
    {
        $this->logger->info('User completed flower cycle', [
            'user_id' => $event->getUser()->getId(),
            'completed_flower' => $event->getCompletedFlower()->getName(),
            'next_flower' => $event->getNextFlower()?->getName(),
            'wallet_amount' => $event->getWalletAmount()
        ]);
    }
}
