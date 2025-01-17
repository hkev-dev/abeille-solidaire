<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Flower;
use App\Entity\Donation;
use App\Repository\DonationRepository;
use Random\RandomException;
use App\Repository\UserRepository;
use App\Repository\FlowerRepository;
use App\Entity\QueuedReferralPlacement;
use Doctrine\ORM\EntityManagerInterface;

class ReferralService
{
    private const REFERRAL_CODE_LENGTH = 32;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly FlowerRepository $flowerRepository,
        private readonly MatrixPlacementService $matrixPlacementService,
        private readonly DonationRepository $donationRepository,
        private readonly ReferralCodeService $referralCodeService
    ) {
    }

    /**
     * @throws RandomException
     */
    public function setupNewUser(User $user, User $referrer): void
    {
        // Set referral relationship
        $user->setReferrer($referrer);

        // Generate unique referral code
        $user->setReferralCode($this->generateUniqueReferralCode($user));

        // Set initial flower (Violette)
        $violetteFlower = $this->flowerRepository->findOneBy(['name' => 'Violette']);
        if (!$violetteFlower) {
            throw new \RuntimeException('Violette flower not found in database');
        }
        $user->setCurrentFlower($violetteFlower);

        // Initialize wallet balance
        $user->setWalletBalance(0.0);

        // Set waiting room timestamp
        $user->setWaitingSince(new \DateTime());

        // Set initial registration payment status
        $user->setRegistrationPaymentStatus('pending');

        $this->entityManager->persist($user);
    }

    public function validateReferralCode(string $code): ?User
    {
        $referrer = $this->userRepository->findOneBy(['referralCode' => $code]);

        if (!$referrer) {
            return null;
        }

        // Check if referrer is verified and has completed registration payment
        if (!$referrer->isVerified() || $referrer->getRegistrationPaymentStatus() !== 'completed') {
            return null;
        }

        return $referrer;
    }

    public function getReferralPath(User $user): array
    {
        $path = [];
        $current = $user;

        while ($current->getReferrer() !== null) {
            $path[] = $current->getReferrer();
            $current = $current->getReferrer();

            // Prevent infinite loops in case of circular references
            if (count($path) > 10) {
                break;
            }
        }

        return $path;
    }

    /**
     * @throws \RuntimeException
     */
    private function generateUniqueReferralCode(User $user): string
    {
        try {
            // Try to generate code with user's first name prefix
            $prefix = substr($user->getFirstName(), 0, 2);
            return $this->referralCodeService->generateUniqueReferralCode($prefix);
        } catch (\InvalidArgumentException $e) {
            // Fall back to random prefix if custom prefix fails
            return $this->referralCodeService->generateUniqueReferralCode();
        }
    }

    public function getDirectReferrals(User $user): array
    {
        return $this->userRepository->findBy(
            ['referrer' => $user],
            ['createdAt' => 'ASC'],
            4
        );
    }

    public function canAcceptNewReferrals(User $user): bool
    {
        $directReferrals = $this->getDirectReferrals($user);
        return count($directReferrals) < 4;
    }

    public function followReferrerInFlower(User $user, Flower $flower): void
    {
        $referrer = $user->getReferrer();
        if (!$referrer) {
            return;
        }

        $this->entityManager->beginTransaction();
        try {
            // Check referrer's status in this flower
            $referrerStatus = $this->getReferrerFlowerStatus($referrer, $flower);

            switch ($referrerStatus['status']) {
                case 'ACTIVE':
                    // Referrer is active in this flower, place referral in their matrix
                    $this->placeReferralInMatrix($user, $referrer, $flower);
                    break;

                case 'COMPLETED':
                    // Referrer has completed this flower, follow to next flower
                    $nextFlower = $this->flowerRepository->findNextFlower($flower);
                    if ($nextFlower) {
                        $this->followReferrerInFlower($user, $nextFlower);
                    }
                    break;

                case 'PENDING':
                    // Referrer hasn't reached this flower yet, place in waiting queue
                    $this->queueReferralForFlower($user, $flower);
                    break;

                default:
                    throw new \RuntimeException('Invalid referrer status');
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    private function getReferrerFlowerStatus(User $referrer, Flower $flower): array
    {
        $currentFlower = $referrer->getCurrentFlower();

        if (!$currentFlower) {
            return ['status' => 'PENDING'];
        }

        if ($currentFlower->getId() === $flower->getId()) {
            // Check if referrer has space in their matrix
            if ($this->canAcceptNewReferralsInFlower($referrer, $flower)) {
                return ['status' => 'ACTIVE'];
            }
            return ['status' => 'COMPLETED'];
        }

        if ($currentFlower->getId() > $flower->getId()) {
            return ['status' => 'COMPLETED'];
        }

        return ['status' => 'PENDING'];
    }

    private function placeReferralInMatrix(User $referral, User $referrer, Flower $flower): void
    {
        // Validate referral limit
        if (!$this->canAcceptNewReferralsInFlower($referrer, $flower)) {
            throw new \RuntimeException('Referrer has reached maximum referrals for this flower');
        }

        // Find next available position in referrer's matrix
        $position = $this->matrixPlacementService->findNextReferralPosition($referrer, $flower);
        if (!$position) {
            throw new \RuntimeException('No available positions in referrer\'s matrix');
        }

        // Create referral placement donation to track the position
        $donation = new Donation();
        $donation->setDonationType('referral_placement')
            ->setDonor($referrer)
            ->setRecipient($referral)
            ->setFlower($flower)
            ->setCyclePosition($position)
            ->setAmount($flower->getDonationAmount())
            ->setTransactionDate(new \DateTimeImmutable());

        $this->entityManager->persist($donation);
        $referral->setCurrentFlower($flower);
    }

    private function queueReferralForFlower(User $referral, Flower $flower): void
    {
        // Store the queued placement in a new entity or status
        $queuedPlacement = new QueuedReferralPlacement();
        $queuedPlacement->setReferral($referral)
            ->setFlower($flower)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($queuedPlacement);
    }

    public function canAcceptNewReferralsInFlower(User $user, Flower $flower): bool
    {
        $referralCount = $this->donationRepository->countReferralPlacements($user, $flower);
        return $referralCount < 4;
    }

    public function processQueuedReferrals(User $referrer, Flower $flower): void
    {
        $queuedReferrals = $this->getQueuedReferrals($referrer, $flower);

        foreach ($queuedReferrals as $queuedReferral) {
            try {
                $this->followReferrerInFlower($queuedReferral->getReferral(), $flower);
                $this->entityManager->remove($queuedReferral);
            } catch (\Exception $e) {
                // Log error but continue processing others
                continue;
            }
        }

        $this->entityManager->flush();
    }

    public function getQueuedReferrals(User $referrer, Flower $flower): array
    {
        return $this->entityManager
            ->getRepository(QueuedReferralPlacement::class)
            ->findBy(
                [
                    'referral.referrer' => $referrer,
                    'flower' => $flower
                ],
                ['queuedAt' => 'ASC']
            );
    }
}
