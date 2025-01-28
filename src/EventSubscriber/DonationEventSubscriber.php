<?php

namespace App\EventSubscriber;

use App\Event\DonationProcessedEvent;
use App\Service\DonationService;
use App\Service\MatrixService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class DonationEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly DonationService $donationService,
        private readonly MatrixService $matrixService,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            DonationProcessedEvent::NAME => 'onDonationProcessed'
        ];
    }

    public function onDonationProcessed(DonationProcessedEvent $event): void
    {
        $donation = $event->getDonation();
        $recipient = $event->getRecipient();

        try {
            // Only check cycle completion for registration donations
            if ($donation->getDonationType() === 'registration' && 
                $donation->getPaymentStatus() === 'completed') {
                
                $this->logger->info('Processing registration donation', [
                    'donation_id' => $donation->getId(),
                    'recipient_id' => $recipient->getId()
                ]);

                // Check if the recipient has completed their cycle
                if ($this->donationService->hasCompletedCycle($recipient)) {
                    $this->logger->info('Cycle completed, processing completion', [
                        'user_id' => $recipient->getId(),
                        'current_flower' => $recipient->getCurrentFlower()->getName()
                    ]);

                    // Process the cycle completion (this will create solidarity donation
                    // and trigger flower progression)
                    $this->matrixService->processUserCycleCompletion($recipient);
                    $this->em->flush();

                    $this->logger->info('Cycle completion processed successfully', [
                        'user_id' => $recipient->getId(),
                        'new_flower' => $recipient->getCurrentFlower()->getName()
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error processing donation event', [
                'donation_id' => $donation->getId(),
                'recipient_id' => $recipient->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}