<?php

namespace App\Service;

use App\Entity\Flower;
use App\Entity\User;
use App\Entity\Donation;
use App\Repository\EarningRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FlowerService
{
    public const MAX_CYCLE_ITERATIONS = 10;

    protected EntityManagerInterface $em;
    protected EventDispatcherInterface $eventDispatcher;
    protected MembershipService $membershipService;
    private EarningRepository $earningRepository;

    public function __construct(
        EntityManagerInterface   $em,
        EventDispatcherInterface $eventDispatcher,
        MembershipService        $membershipService, EarningRepository $earningRepository
    ) {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->membershipService = $membershipService;
        $this->earningRepository = $earningRepository;
    }

    public function getUserCycleIterations(User $user, Flower $flower): int
    {
        return $this->em->createQueryBuilder()
            ->select('COUNT(d.id)')
            ->from(Donation::class, 'd')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType = :type')
            ->andWhere('d.paymentStatus = :status')
            ->setParameter('user', $user)
            ->setParameter('flower', $flower)
            ->setParameter('type', Donation::TYPE_REGISTRATION)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function canProgressToNextFlower(Donation $donation): bool
    {
//        $currentFlower = $donation->getCurrentFlower();
//        $cycleIterations = $this->getUserCycleIterations($user, $currentFlower);
        
//        return $cycleIterations < self::MAX_CYCLE_ITERATIONS;

        return true;
    }

    public function validateFlowerProgression(Donation $donation): bool
    {
        return $this->canProgressToNextFlower($donation);
    }

    public function getCurrentCycleCount(User $user): int
    {
        return $this->em->createQueryBuilder()
            ->select('COUNT(d.id)')
            ->from(Donation::class, 'd')
            ->where('d.recipient = :user')
            ->andWhere('d.flower = :flower')
            ->andWhere('d.donationType = :type')
            ->andWhere('d.paymentStatus = :status')
            ->setParameter('user', $user)
            ->setParameter('flower', $user->getCurrentFlower())
            ->setParameter('type', Donation::TYPE_REGISTRATION)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasReachedCycleLimit(User $user): bool
    {
        $cycleCount = $this->getCurrentCycleCount($user);
        return $cycleCount >= 10;
    }

    public function getNextFlower(Flower $flower): ?Flower
    {
        return $this->em->createQueryBuilder()
            ->select('f')
            ->from(Flower::class, 'f')
            ->where('f.level = :level')
            ->setParameter('level', $flower->getLevel() + 1)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getFirstFlower(): Flower
    {
        return $this->em->createQueryBuilder()
            ->select('f')
            ->from(Flower::class, 'f')
            ->where('f.level = 1')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    public static function getNumberOfSlotByLevel($level): int
    {
        if ($level < 1) {
            return 0;
        }

        return 4 ** $level;
    }

    public static function getLevelExpectedEarning(int $level): float
    {
        $expextedEarning = DonationService::REGISTRATION_FEE;

        for($i = 0; $i < $level; $i++) {
            $expextedEarning = $expextedEarning / 2;
        }

        return $expextedEarning;
    }

    public static function getLevelTotalAmountExpected(int $level): float
    {
        return self::getNumberOfSlotByLevel($level) * self::getLevelExpectedEarning($level);
    }

    public function getReceivedAmount(Donation $donation, Flower $flower): float
    {
        $earnings = $this->earningRepository->findBy(['beneficiary' => $donation, 'flower' => $flower]);

        return array_reduce($earnings, function($carry, $item) {
            return $carry + $item->getAmount();
        }, 0.0);
    }
}
