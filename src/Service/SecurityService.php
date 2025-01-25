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
    private const MAX_ATTEMPTS = 10;
    private const RATE_LIMIT_INTERVAL = '1 hour';
    private const ACCOUNT_LOCKOUT_DURATION = 3600; // 1 hour
    private const SESSION_EXPIRY = 7200; // 2 hours

    private const ROUTES = [
        'login' => 'app.login',
        'register' => 'app.register',
        'payment' => 'app.registration.payment',
        'waiting_room' => 'app.waiting_room',
        'membership_renew' => 'app.membership.renew',
        'membership_status' => 'app.membership.status',
        'dashboard' => 'app.user.dashboard'
    ];

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

    public function isAnnualMembershipExpired(User $user): bool
    {
        return $this->membershipService->isExpired($user);
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

    /**
     * Simple status checks without redirection
     */
    public function canAccessDashboard(User $user): bool
    {
        try {
            $this->validateUserStatus($user);
            return true;
        } catch (UserAccessException $e) {
            $this->logger->info('Dashboard access denied', [
                'user_id' => $user->getId(),
                'reason' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function handleSessionExpiry(): void
    {
        $session = $this->requestStack->getSession();
        $lastActivity = $session->get('last_activity', 0);

        if (time() - $lastActivity > self::SESSION_EXPIRY) {
            $session->invalidate();
            throw new UserAccessException(
                'session_expired',
                'Your session has expired. Please log in again.',
                ['login_url' => '/login']
            );
        }

        $session->set('last_activity', time());
    }

    private function isAccountLocked(SessionInterface $session): bool
    {
        $lockedUntil = $session->get('login_locked_until', 0);
        return $lockedUntil > time();
    }

    private function getUnlockTime(SessionInterface $session): int
    {
        return $session->get('login_locked_until', 0);
    }
}
