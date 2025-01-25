<?php

namespace App\Service;

use App\Entity\User;
use ReCaptcha\ReCaptcha;
use Psr\Log\LoggerInterface;
use App\Exception\UserAccessException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\RateLimiter\RateLimiterFactory;
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

    private const ALLOWED_ROUTES_WITH_EXPIRED_MEMBERSHIP = [
        'app.membership.renew',
        'app.membership.check_payment',
        'app.membership.check_payment_status',
        'app.membership.waiting_room',
        'app.membership.status',
        'app.webhook.coinbase'
    ];

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
     * @param User $user
     * @param bool $skipMembershipCheck Optional, defaults to false
     */
    public function validateUserStatus(User $user, bool $skipMembershipCheck = false): void
    {
        $this->validateBasicStatus($user);
        if (!$skipMembershipCheck) $this->validateMembership($user);
    }

    private function validateBasicStatus(User $user): void
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

        // Check if user has a flower assigned
        if (!$user->getCurrentFlower()) {
            throw new UserAccessException(
                'pending_payment',
                'No flower assigned. Please complete registration.');
        }
    }

    private function validateMembership(User $user): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $currentRoute = $request?->attributes->get('_route');

        // Skip membership check for allowed routes
        if (in_array($currentRoute, self::ALLOWED_ROUTES_WITH_EXPIRED_MEMBERSHIP)) {
            return;
        }

        if ($this->membershipService->isExpired($user)) {
            throw new UserAccessException('membership_expired', 'Annual membership has expired.');
        }
    }
}
