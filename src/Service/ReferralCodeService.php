<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;

class ReferralCodeService
{
    private const CODE_LENGTH = 8;
    private const CHAR_SETS = [
        'ABCDEFGHJKLMNPQRSTUVWXYZ',  // Uppercase without confusing characters (I, O)
        '23456789'                    // Numbers without confusing digits (0, 1)
    ];
    private const PREFIX_LENGTH = 2;
    private const CHECKSUM_LENGTH = 1;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CacheItemPoolInterface $cache
    ) {
    }

    public function generateUniqueReferralCode(string $prefix = ''): string
    {
        $attempts = 0;
        $maxAttempts = 5;

        do {
            if ($attempts >= $maxAttempts) {
                throw new \RuntimeException('Failed to generate unique referral code');
            }

            $code = $this->generateCode($prefix);
            $exists = $this->isCodeTaken($code);
            
            $attempts++;
        } while ($exists);

        return $code;
    }

    private function generateCode(string $prefix = ''): string
    {
        $finalPrefix = $this->validateOrGeneratePrefix($prefix);
        $mainCode = $this->generateMainPart();
        $checksum = $this->calculateChecksum($finalPrefix . $mainCode);

        return $finalPrefix . $mainCode . $checksum;
    }

    private function validateOrGeneratePrefix(string $prefix): string
    {
        if (empty($prefix)) {
            return substr(str_shuffle(self::CHAR_SETS[0]), 0, self::PREFIX_LENGTH);
        }

        $prefix = strtoupper($prefix);
        if (strlen($prefix) !== self::PREFIX_LENGTH) {
            throw new \InvalidArgumentException('Invalid prefix length');
        }

        if (!preg_match('/^[' . preg_quote(self::CHAR_SETS[0], '/') . ']+$/', $prefix)) {
            throw new \InvalidArgumentException('Invalid prefix characters');
        }

        return $prefix;
    }

    private function generateMainPart(): string
    {
        $mainLength = self::CODE_LENGTH - self::PREFIX_LENGTH - self::CHECKSUM_LENGTH;
        $result = '';
        
        for ($i = 0; $i < $mainLength; $i++) {
            $charSet = self::CHAR_SETS[$i % 2];
            $result .= $charSet[random_int(0, strlen($charSet) - 1)];
        }

        return $result;
    }

    private function calculateChecksum(string $code): string
    {
        $sum = 0;
        $lookup = array_flip(str_split(implode('', self::CHAR_SETS)));
        
        for ($i = 0; $i < strlen($code); $i++) {
            $value = $lookup[$code[$i]] ?? 0;
            $sum += ($i % 2 === 0) ? $value : ($value * 2 % 35);
        }

        return self::CHAR_SETS[0][$sum % strlen(self::CHAR_SETS[0])];
    }

    private function isCodeTaken(string $code): bool
    {
        $cacheKey = 'referral_code_' . $code;
        
        return $this->cache->get($cacheKey, function() use ($code) {
            return $this->entityManager->getRepository(User::class)
                ->findOneBy(['referralCode' => $code]) !== null;
        });
    }

    public function validateCode(string $code): bool
    {
        if (strlen($code) !== self::CODE_LENGTH) {
            return false;
        }

        $prefix = substr($code, 0, self::PREFIX_LENGTH);
        $mainPart = substr($code, self::PREFIX_LENGTH, -self::CHECKSUM_LENGTH);
        $checksum = substr($code, -self::CHECKSUM_LENGTH);

        if (!$this->validateFormat($prefix, $mainPart)) {
            return false;
        }

        $expectedChecksum = $this->calculateChecksum($prefix . $mainPart);
        return $checksum === $expectedChecksum;
    }

    private function validateFormat(string $prefix, string $mainPart): bool
    {
        if (!preg_match('/^[' . preg_quote(self::CHAR_SETS[0], '/') . ']{' . self::PREFIX_LENGTH . '}$/', $prefix)) {
            return false;
        }

        $pattern = '/^[' . preg_quote(implode('', self::CHAR_SETS), '/') . ']+$/';
        return preg_match($pattern, $mainPart);
    }
}
