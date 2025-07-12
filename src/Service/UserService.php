<?php

namespace App\Service;

use App\Entity\Donation;
use App\Entity\User;
use App\Entity\Withdrawal;
use App\Repository\DonationRepository;
use App\Repository\WithdrawalRepository;

readonly class UserService
{
    public function __construct(private WalletService $walletService)
    {

    }

    public function isEligibleForWithdrawal(User $user): bool
    {
        return $user->isKycVerified() &&      // KYC verification completed
//            $this->walletService->getWalletBalance($user) >= Withdrawal::MIN_AMOUNT &&    // Minimum withdrawal amount
            $user->hasPaymentMethods() &&    // Annual membership is active
            $user->hasPaidAnnualFee() &&    // Annual membership is active
//            $this->hasRequiredMatrixDepthForWithdrawal() &&       // Has required matrix depth
            $user->hasProject();             // Has at least one project
    }

    public function isLastWithdrawalHandled(User $user): bool
{
    $withdrawals = $user->getWithdrawals();

    if ($withdrawals->isEmpty()) {
        return false;
    }

    /** @var Withdrawal|null $lastWithdrawal */
    $lastWithdrawal = $withdrawals->toArray();

    usort($lastWithdrawal, function (Withdrawal $a, Withdrawal $b) {
        return $b->getRequestedAt() <=> $a->getRequestedAt();
    });

    $lastWithdrawal = $lastWithdrawal[0] ?? null;

    if (!$lastWithdrawal) {
        return false;
    }

    return in_array($lastWithdrawal->getStatus(), [
        Withdrawal::STATUS_PROCESSED,
        Withdrawal::STATUS_REJECTED,
        Withdrawal::STATUS_FAILED,
    ]);
}

    public function getLastHandledWithdrawal(User $user): ?Withdrawal
    {
        $withdrawals = $user->getWithdrawals();

        if ($withdrawals->isEmpty()) {
            return null;
        }

        $sorted = $withdrawals->toArray();
        usort($sorted, fn(Withdrawal $a, Withdrawal $b) => $b->getRequestedAt() <=> $a->getRequestedAt());

        foreach ($sorted as $withdrawal) {
            if (in_array($withdrawal->getStatus(), [
                Withdrawal::STATUS_PENDING,
            ])) {
                return $withdrawal;
            }
        }

        return null;
    }

}