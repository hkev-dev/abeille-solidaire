<?php

namespace App\Service;

use App\Entity\Donation;
use App\Entity\User;
use App\Entity\Withdrawal;
use App\Repository\DonationRepository;
use App\Repository\WithdrawalRepository;

readonly class WalletService
{
    public function __construct(private WithdrawalRepository $withdrawalRepository, private DonationRepository $donationRepository)
    {

    }

    public function getWalletBalance(User $user): float
    {
        if ($user->getWalletBalance() !== null) {
            return $user->getWalletBalance();
        }

        $processedWithdrawals = $this->withdrawalRepository->findBy(['user' => $user, 'status' => Withdrawal::STATUS_PROCESSED], ['requestedAt' => 'DESC']);
        $donationsMade = $this->donationRepository->findBy(['donor' => $user], ['paymentCompletedAt' => 'DESC']);
        $balance = 0.0;

        foreach ($donationsMade as $donation) {
            $balance += $donation->getEarningsAmount();
        }

        foreach ($processedWithdrawals as $withdrawal) {
            $balance -= $withdrawal->getAmount();
        }

        $user->setWalletBalance($balance);

        return $balance;
    }
}