<?php

namespace App\EventSubscriber;

use App\Entity\Donation;
use App\Event\DonationProcessParentLevelUpEvent;
use App\Event\DonationPositionedInMatrixEvent;
use App\Event\DonationProcessedEvent;
use App\Service\DonationService;
use App\Service\FlowerService;
use App\Service\MatrixService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DonationEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly DonationService $donationService,
        private readonly MatrixService $matrixService,
        private readonly FlowerService $flowerService,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            DonationProcessedEvent::NAME => 'onDonationProcessed',
            DonationProcessParentLevelUpEvent::NAME => 'processParentLevelUp',
            DonationPositionedInMatrixEvent::NAME => 'onDonationPositionedInMatrix'
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

    public function processParentLevelUp(DonationProcessParentLevelUpEvent $event): void
    {
        $donation = $event->getDonation();

        if ($donation->canLevelUp()){
            $donation->setFlower($this->flowerService->getNextFlower($donation->getFlower()));
            $this->em->persist($donation);
            $this->em->flush();

        }

        if ($donation->getParent()){
            $event = new DonationProcessParentLevelUpEvent($donation->getParent());
            $this->eventDispatcher->dispatch($event, $event::NAME);
        }

        $this->em->flush();
    }

    public function onDonationPositionedInMatrix(DonationPositionedInMatrixEvent $event): void
    {
        $donation = $event->getDonation();
        $this->donationService->calculateEarnings($donation, $donation->getAmount());
    }
}