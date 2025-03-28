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
}