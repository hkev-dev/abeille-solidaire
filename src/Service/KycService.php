<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use App\Entity\KycVerification;
use App\Event\KycVerificationEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class KycService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly string $kycUploadsDir,
        private readonly string $kycProvider
    ) {
    }

    public function getKycStatus(User $user): array
    {
        $verification = $this->entityManager->getRepository(KycVerification::class)
            ->findOneBy(['user' => $user], ['submittedAt' => 'DESC']);

        return [
            'isVerified' => $user->isKycVerified(),
            'verifiedAt' => $user->getKycVerifiedAt(),
            'canSubmit' => !$user->isKycVerified() && !$verification,
            'pendingVerification' => $verification && $verification->getStatus() === self::STATUS_PENDING
        ];
    }

    public function submitVerification(User $user, array $data, array $files): bool
    {
        try {
            // Verify user is in valid matrix position
            if (!$user->getMatrixPosition() || !$user->getMatrixDepth()) {
                throw new \RuntimeException('User must be placed in matrix before KYC verification');
            }

            // Store files securely
            $documentPaths = $this->storeKycDocuments($user, $files);

            // Create verification record
            $verification = new KycVerification();
            $verification->setUser($user)
                ->setReferenceId('KYC_' . uniqid())
                ->setStatus(self::STATUS_PENDING)
                ->setDocumentPaths($documentPaths)
                ->setSubmittedData(array_merge($data, [
                    'matrix_position' => $user->getMatrixPosition(),
                    'matrix_depth' => $user->getMatrixDepth(),
                    'current_flower' => $user->getCurrentFlower()->getName()
                ]))
                ->setSubmittedAt(new \DateTime());

            $this->entityManager->persist($verification);
            $this->entityManager->flush();

            // Dispatch event
            $event = new KycVerificationEvent($user, 'submitted');
            $this->eventDispatcher->dispatch($event, KycVerificationEvent::SUBMITTED);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('KYC submission failed', [
                'user_id' => $user->getId(),
                'matrix_position' => $user->getMatrixPosition(),
                'matrix_depth' => $user->getMatrixDepth(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function hasPendingVerification(User $user): bool
    {
        return !$user->isKycVerified();
    }

    private function storeKycDocuments(User $user, array $files): array
    {
        $paths = [];
        foreach ($files as $type => $file) {
            if ($file instanceof UploadedFile) {
                $filename = sprintf(
                    'kyc_%s_%s_%s.%s',
                    $user->getId(),
                    $type,
                    uniqid(),
                    $file->getClientOriginalExtension()
                );
                $file->move($this->kycUploadsDir, $filename);
                $paths[$type] = $filename;
            }
        }
        return $paths;
    }

    public function approveVerification(string $referenceId, string $adminComment = null): void
    {
        $verification = $this->getVerification($referenceId);
        $user = $verification->getUser();

        $verification->setStatus(self::STATUS_APPROVED)
            ->setAdminComment($adminComment)
            ->setProcessedAt(new \DateTime());

        $user->setIsKycVerified(true);

        $this->entityManager->flush();

        $event = new KycVerificationEvent($user, 'approved');
        $this->eventDispatcher->dispatch($event, KycVerificationEvent::APPROVED);
    }

    public function rejectVerification(string $referenceId, string $reason): void
    {
        $verification = $this->getVerification($referenceId);

        $verification->setStatus(self::STATUS_REJECTED)
            ->setAdminComment($reason)
            ->setProcessedAt(new \DateTime());

        $this->entityManager->flush();

        $event = new KycVerificationEvent($verification->getUser(), 'rejected', $reason);
        $this->eventDispatcher->dispatch($event, KycVerificationEvent::REJECTED);
    }

    public function getPendingVerifications(): array
    {
        return $this->entityManager->getRepository(KycVerification::class)
            ->findBy(['status' => self::STATUS_PENDING], ['submittedAt' => 'ASC']);
    }

    private function getVerification(string $referenceId): KycVerification
    {
        $verification = $this->entityManager->getRepository(KycVerification::class)
            ->findOneBy(['referenceId' => $referenceId]);

        if (!$verification) {
            throw new \Exception('Verification not found');
        }

        return $verification;
    }
}
