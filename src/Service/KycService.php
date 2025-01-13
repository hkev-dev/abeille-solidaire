<?php

namespace App\Service;

use App\Entity\User;
use App\Event\KycVerificationEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class KycService
{
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
        return [
            'isVerified' => $user->isKycVerified(),
            'verifiedAt' => $user->getKycVerifiedAt(),
            'provider' => $user->getKycProvider(),
            'canSubmit' => !$user->isKycVerified() && !$this->hasPendingVerification($user),
            'pendingVerification' => $this->hasPendingVerification($user)
        ];
    }

    public function submitVerification(User $user, array $data, array $files): bool
    {
        try {
            // Store files securely
            $documentPaths = $this->storeKycDocuments($user, $files);

            // Submit to KYC provider
            $verificationId = $this->submitToProvider($user, $data, $documentPaths);

            // Update user status
            $user->setKycReferenceId($verificationId)
                 ->setKycProvider($this->kycProvider);
            
            $this->entityManager->flush();

            // Dispatch event
            $event = new KycVerificationEvent($user, 'submitted');
            $this->eventDispatcher->dispatch($event, KycVerificationEvent::SUBMITTED);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('KYC submission failed', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function handleVerificationWebhook(array $data): void
    {
        try {
            $user = $this->entityManager->getRepository(User::class)
                ->findOneBy(['kycReferenceId' => $data['reference_id']]);

            if (!$user) {
                throw new \Exception('User not found for KYC reference');
            }

            if ($data['status'] === 'approved') {
                $user->setIsKycVerified(true);
                $this->entityManager->flush();

                $event = new KycVerificationEvent($user, 'approved');
                $this->eventDispatcher->dispatch($event, KycVerificationEvent::APPROVED);
            } elseif ($data['status'] === 'rejected') {
                $event = new KycVerificationEvent($user, 'rejected', $data['reason'] ?? null);
                $this->eventDispatcher->dispatch($event, KycVerificationEvent::REJECTED);
            }

        } catch (\Exception $e) {
            $this->logger->error('KYC webhook processing failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    private function hasPendingVerification(User $user): bool
    {
        return $user->getKycReferenceId() !== null && !$user->isKycVerified();
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

    private function submitToProvider(User $user, array $data, array $documentPaths): string
    {
        // Implement KYC provider API call here
        // This is a placeholder for the actual KYC provider integration
        return uniqid('kyc_');
    }
}
