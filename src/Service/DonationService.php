<?php

namespace App\Service;

use App\Entity\Donation;
use App\Entity\Earning;
use App\Entity\User;
use App\Entity\Flower;
use App\Repository\EarningRepository;
use App\Repository\FlowerRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Event\DonationProcessedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DonationService
{
    public const MEMBERSHIP_FEE = 25.00;
    public const REGISTRATION_FEE = 25.00;
    public const SUPPLEMENTARY_FEE = 25.00;

    protected EntityManagerInterface $em;
    protected EventDispatcherInterface $eventDispatcher;
    private FlowerRepository $flowerRepository;

    public function __construct(
        EntityManagerInterface   $em,
        EventDispatcherInterface $eventDispatcher,
        FlowerRepository $flowerRepository
    )
    {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->flowerRepository = $flowerRepository;
    }

    public function createDonation(
        User    $donor,
        User    $recipient,
        float   $amount,
        string  $donationType,
        ?Flower $flower = null,
        string  $paymentStatus = 'pending'
    ): Donation
    {
        $donation = new Donation();
        $donation
            ->setDonor($donor)
            ->setRecipient($recipient)
            ->setAmount($amount)
            ->setDonationType($donationType)
            ->setFlower($flower)
            ->setPaymentStatus($paymentStatus)
            ->setTransactionDate(new \DateTimeImmutable());

        $this->em->persist($donation);
        $this->em->flush();

        if ($paymentStatus === 'completed') {
            $this->eventDispatcher->dispatch(new DonationProcessedEvent($donation), DonationProcessedEvent::NAME);
        }

        return $donation;
    }

    public function createRegistrationDonation(User $user): Donation
    {
        $flower = $this->flowerRepository->findOneBy(['level' => 1]);

        $donation = new Donation();
        $donation
            ->setDonor($user)
            ->setAmount(DonationService::REGISTRATION_FEE)
            ->setDonationType(Donation::TYPE_REGISTRATION)
            ->setFlower($flower)
            ->setPaymentStatus('pending')
            ->setTransactionDate(new \DateTimeImmutable());

        $this->em->persist($donation);
        $this->em->flush();

        return $donation;
    }

    public function createSolidarityDonation(User $donor, float $amount, ?Flower $flower = null): ?Donation
    {
        // Always find the Abeille Solidaire user (root user) for solidarity donations
        $rootUser = $this->findAbeilleSolidaireUser();

        $donation = $this->createDonation(
            $donor,
            $rootUser,
            $amount,
            Donation::TYPE_SOLIDARITY,
            $flower,
            'completed'
        );

        $donation->setPaymentProvider('internal');

        // Dispatch donation processed event with NAME constant
        $this->eventDispatcher->dispatch(new DonationProcessedEvent($donation), DonationProcessedEvent::NAME);

        $this->em->flush();

        return $donation;
    }

    public function createSupplementaryDonation(User $donor): ?Donation
    {
        $flower = $this->flowerRepository->findOneBy(['level' => 1]);

        $donation = new Donation();
        $donation
            ->setDonor($donor)
            ->setAmount(DonationService::REGISTRATION_FEE)
            ->setDonationType(Donation::TYPE_SUPPLEMENTARY)
            ->setFlower($flower)
            ->setPaymentStatus('pending')
            ->setTransactionDate(new \DateTimeImmutable());

        $this->em->persist($donation);
        $this->em->flush();

        return $donation;
    }

    private function findAbeilleSolidaireUser(): User
    {
        $rootUser = $this->em->getRepository(User::class)
            ->findOneBy(['matrixDepth' => 0]);

        if (!$rootUser) {
            throw new \RuntimeException('Abeille Solidaire user (root) not found');
        }

        return $rootUser;
    }

    private function findUserWithFewestChildren(): User
    {
        $qb = $this->em->createQueryBuilder();
        $result = $qb->select('u')
            ->from(User::class, 'u')
            ->leftJoin('u.children', 'c')
            ->where('u.registrationPaymentStatus = :status')
            ->setParameter('status', 'completed')
            ->groupBy('u.id')
            ->orderBy('COUNT(c.id)', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$result) {
            throw new \RuntimeException('No eligible user found for supplementary donation');
        }

        return $result;
    }

    public function hasCompletedCycle(User $user): bool
    {
        // Get completed registration donations for the current flower
        $completedDonations = $this->em->createQueryBuilder()
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

        // Get direct children count
        $children = $this->em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(User::class, 'c')
            ->where('c.parent = :parent')
            ->andWhere('c.registrationPaymentStatus = :status')
            ->setParameter('parent', $user)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        // Both conditions must be met:
        // 1. Have 4 completed registration donations in current flower
        // 2. Have 4 direct children with completed registration
        return $completedDonations >= 4 && $children >= 4;
    }

    public function getCycleProgress(User $user): array
    {
        $completedDonations = $this->em->createQueryBuilder()
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

        $children = $this->em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(User::class, 'c')
            ->where('c.parent = :parent')
            ->andWhere('c.registrationPaymentStatus = :status')
            ->setParameter('parent', $user)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'donations' => $completedDonations,
            'children' => $children,
            'isComplete' => ($completedDonations >= 4 && $children >= 4)
        ];
    }

    public function calculateEarnings(Donation $donation, float $amount, ?Earning $earningSource = null): void
    {
        $earning = new Earning();

        $earning->setDonor($donation)
            ->setFlower($donation->getFlower());

        $rootDonation = $this->em->getRepository(Donation::class)
            ->findOneBy(['paymentStatus' => 'completed'], ['paymentCompletedAt' => 'ASC']);

        if ($donation->getParent()) {
            $share = $amount * Donation::PAYMENT_SHARE;
            $earning->setAmount($share);

            $donation->getParent()->addEarning($earning);

            $this->calculateEarnings($donation->getParent(), $share, $earningSource ?? $earning); // Recursive call for the parent
        } else {
            // If no parent, the first member gets the remaining amount
            if ($donation->getId() === $rootDonation->getId()) {
                $earning->setAmount($amount);

                if ($earningSource) {
                    $earning->setDonor($earningSource->getDonor());
                }

                $donation->addEarning($earning);
            }
        }
    }

    public function isUserBeneficiary(Donation $donation, User $user): bool
    {
        $earning = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Earning::class, 'e')
            ->leftJoin('e.beneficiary', 'beneficiary')
            ->where('e.donor = :donation')
            ->andWhere('beneficiary.donor = :user')
            ->setParameter('donation', $donation)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        return $earning !== null;
    }
}
