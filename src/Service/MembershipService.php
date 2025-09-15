<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Membership;
use App\Entity\Donation;
use App\Repository\MembershipRepository;
use App\Service\Payment\PaymentServiceInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\MembershipExpiredEvent;
use App\Event\MembershipActivatedEvent;

class MembershipService
{
    public const MEMBERSHIP_FEE = 25.0;
    public const EXPIRATION_WARNING_DAYS = [30, 15, 7, 3, 1];

    public function __construct(
        private readonly EntityManagerInterface   $entityManager,
        private readonly MembershipRepository   $membershipRepository,
        private readonly LoggerInterface          $logger,
        private readonly EventDispatcherInterface $eventDispatcher
    )
    {
    }

    public function createMembership(User $user): Membership
    {
        $membership = new Membership();
        $membership->setUser($user);

        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        return $membership;
    }

    /**
     * @throws ReflectionException
     */
    public function activateMembership(Membership $membership, string $paymentReference): Membership
    {
        $callerClass = ObjectService::getCallerClass(PaymentServiceInterface::class);

        /*if (!$callerClass) {
            throw new RuntimeException('Membership creation can only be called from a PaymentServiceInterface implementation');
        }*/

        /** @var PaymentServiceInterface $callerClass */

        $membership
            ->setPaymentReference($paymentReference)
            ->setPaymentProvider( $callerClass ? $callerClass::getProvider():'None')
            ->setPaymentStatus('completed')
            ->setPaymentCompletedAt(new DateTime())
            ->activate();

        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new MembershipActivatedEvent($membership));

        return $membership;
    }

    public function checkExpirationWarnings(): array
    {
        $warnings = [];
        $qb = $this->entityManager->createQueryBuilder();

        foreach (self::EXPIRATION_WARNING_DAYS as $days) {
            $expiringMemberships = $qb->select('m')
                ->from(Membership::class, 'm')
                ->where('m.status = :status')
                ->andWhere('m.endDate BETWEEN :start AND :end')
                ->setParameter('status', Membership::STATUS_ACTIVE)
                ->setParameter('start', new DateTime("+{$days} days"))
                ->setParameter('end', new DateTime("+" . ($days + 1) . " days"))
                ->getQuery()
                ->getResult();

            foreach ($expiringMemberships as $membership) {
                $warnings[] = [
                    'user' => $membership->getUser(),
                    'daysUntilExpiration' => $days,
                    'membership' => $membership
                ];
            }
        }

        return $warnings;
    }

    public function checkAndUpdateExpiredMemberships(): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $expiredMemberships = $qb->select('m')
            ->from(Membership::class, 'm')
            ->where('m.status = :status')
            ->andWhere('m.endDate < :now')
            ->setParameter('status', Membership::STATUS_ACTIVE)
            ->setParameter('now', new DateTime())
            ->getQuery()
            ->getResult();

        foreach ($expiredMemberships as $membership) {
            $membership->setStatus(Membership::STATUS_EXPIRED);
            $this->eventDispatcher->dispatch(new MembershipExpiredEvent($membership));
            $this->logger->info(sprintf(
                'Membership expired for user %s (ID: %d)',
                $membership->getUser()->getEmail(),
                $membership->getUser()->getId()
            ));
        }

        $this->entityManager->flush();
    }

    public function isExpired(User $user): bool
    {
        $currentMembership = $user->getCurrentMembership();
        return !$currentMembership || $currentMembership->isExpired();
    }

    public function getLatestMembership(User $user): ?Membership
    {
        return $user->getLastMembership();
    }

    public function getRenewalAmount(): float
    {
        return self::MEMBERSHIP_FEE;
    }

    /**
     * @param User $user
     * @return Membership[]
     */
    public function getMembershipHistory(User $user): array
    {
        return $this->membershipRepository->findBy(["user" => $user]);
    }
}
