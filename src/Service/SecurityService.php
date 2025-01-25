<?php

namespace App\Service;

use App\Entity\User;
use ReCaptcha\ReCaptcha;
use Psr\Log\LoggerInterface;
use App\Exception\UserAccessException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class SecurityService
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly RateLimiterFactory $registrationLimiter,
        private readonly string $recaptchaSecretKey,
        private readonly LoggerInterface $logger,
        private readonly MembershipService $membershipService,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator
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

    private function getClientIp(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->getClientIp() ?? '127.0.0.1';
    }

    /**
     * Validates user status without handling redirects
     * @throws UserAccessException
     */
    public function validateUserStatus(User $user): void
    {
        // Skip validation for super admin
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return;
        }

        if ($user->getRegistrationPaymentStatus() === 'pending') {
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
}
