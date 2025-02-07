<?php

namespace App\Service;

use App\Entity\User;
use ReCaptcha\ReCaptcha;
use Psr\Log\LoggerInterface;
use App\Exception\UserAccessException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

readonly class SecurityService
{
    private const ADMIN_ROLES = ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];

    public function __construct(
        private RequestStack $requestStack,
        private RateLimiterFactory $registrationLimiter,
        private string $recaptchaSecretKey,
        private LoggerInterface $logger,
        private MembershipService $membershipService
    ) {
    }

    public function checkRegistrationThrottle(): void
    {
        $limiter = $this->registrationLimiter->create($this->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(
                null,
                'Too many registration attempts. Please try again later.'
            );
        }
    }

    public function verifyRecaptcha(?string $recaptchaResponse): bool
    {
        if (!$recaptchaResponse) {
            return false;
        }

        $recaptcha = new ReCaptcha($this->recaptchaSecretKey);
        $result = $recaptcha->setExpectedAction('registration')
            ->setScoreThreshold(0.5)
            ->verify($recaptchaResponse, $this->getClientIp());

        if (!$result->isSuccess()) {
            $this->logger->warning('ReCaptcha verification failed', [
                'errors' => $result->getErrorCodes(),
                'ip' => $this->getClientIp(),
                'score' => $result->getScore(),
                'action' => $result->getAction()
            ]);
            return false;
        }

        return true;
    }

    /**
     * Validates user status and throws exceptions for any validation failures
     * @throws UserAccessException
     */
    public function validateUserStatus(User $user): void
    {
        // Skip validation for admin users
        if (array_intersect(self::ADMIN_ROLES, $user->getRoles())) {
            return;
        }

        if ($user->getMainDonation()->getPaymentStatus() === 'pending') {
            throw new UserAccessException(
                'pending_payment',
                'Registration payment is pending.'
            );
        }

        if ($user->getWaitingSince() !== null) {
            throw new UserAccessException(
                'in_waiting_room',
                'Account is in waiting room.'
            );
        }

        if ($this->membershipService->isExpired($user)) {
            throw new UserAccessException(
                'membership_expired',
                'Annual membership has expired.'
            );
        }
    }

    private function getClientIp(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->getClientIp() ?? '127.0.0.1';
    }

    /**
     * Check if user has admin privileges
     */
    private function isAdminUser(User $user): bool
    {
        return (bool)array_intersect(self::ADMIN_ROLES, $user->getRoles());
    }
}
