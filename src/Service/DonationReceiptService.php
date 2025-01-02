<?php

namespace App\Service;

use App\Entity\Donation;

class DonationReceiptService
{
    /**
     * Generates a donation receipt with all necessary information
     */
    public function generateReceipt(Donation $donation): array
    {
        return [
            'receipt_number' => $this->generateReceiptNumber($donation),
            'donation_date' => $donation->getTransactionDate(),
            'donor' => $donation->getDonor()->getFullName(),
            'recipient' => $donation->getRecipient()->getFullName(),
            'amount' => $donation->getAmount(),
            'donation_type' => $donation->getDonationType(),
            'flower_name' => $donation->getFlower()->getName(),
        ];
    }

    private function generateReceiptNumber(Donation $donation): string
    {
        return sprintf(
            'REC-%s-%d',
            $donation->getTransactionDate()->format('Ymd'),
            $donation->getId()
        );
    }
}
