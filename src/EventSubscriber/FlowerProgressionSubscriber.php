<?php

namespace App\EventSubscriber;

use App\Event\ParentFlowerUpdateEvent;
use App\Service\FlowerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FlowerProgressionSubscriber implements EventSubscriberInterface
{
    private FlowerService $flowerService;
    private EntityManagerInterface $em;

    public function __construct(
        FlowerService $flowerService,
        EntityManagerInterface $em
    ) {
        $this->flowerService = $flowerService;
        $this->em = $em;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ParentFlowerUpdateEvent::NAME => 'onParentFlowerUpdate'
        ];
    }

    public function onParentFlowerUpdate(ParentFlowerUpdateEvent $event): void
    {
        $parent = $event->getParent();
        $children = $event->getChildren();

        if (!$this->flowerService->validateFlowerProgression($parent)) {
            return;
        }

        $nextFlower = $this->flowerService->getNextFlower($parent->getCurrentFlower());
        if (!$nextFlower) {
            return;
        }

        // Update parent's flower
        $parent->setCurrentFlower($nextFlower);

        // Update all children's flowers recursively
        foreach ($children as $child) {
            $this->progressChildFlower($child, $nextFlower);
        }

        $this->em->flush();
    }

    private function progressChildFlower($child, $flower): void
    {
        if (!$child) {
            return;
        }

        // Skip if already at this flower level
        if ($child->getCurrentFlower()->getId() === $flower->getId()) {
            return;
        }

        // Update child's flower
        $child->setCurrentFlower($flower);

        // Get completed children only
        $completedChildren = $child->getChildren()->filter(function($c) {
            return $c->getRegistrationPaymentStatus() === 'completed';
        });

        // Recursively update grandchildren
        foreach ($completedChildren as $grandchild) {
            $this->progressChildFlower($grandchild, $flower);
        }
    }
}
