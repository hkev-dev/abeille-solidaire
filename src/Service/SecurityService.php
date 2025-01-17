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
        'email_verify' => 'app.verify_email',
        'dashboard' => 'landing.home'
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
     * Comprehensive user status validation
     */
    public function validateUserStatus(User $user): void
    {
        // Skip validation for super admin
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return;
        }

        $this->validateRegistrationPayment($user);
        $this->validateEmailVerification($user);
        $this->validateMembership($user);
        $this->validateAccountStatus($user);

        $this->logger->info('User status validated successfully', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail()
        ]);
    }

    public function validateRegistrationPayment(User $user): void
    {
        if ($user->getRegistrationPaymentStatus() === 'pending') {
            throw new UserAccessException(
                'pending_payment',
                'Registration payment is pending.',
                ['payment_url' => $this->urlGenerator->generate(self::ROUTES['payment'], ['id' => $user->getId()])]
            );
        }

        if ($user->getRegistrationPaymentStatus() === 'failed') {
            throw new UserAccessException(
                'payment_failed',
                'Registration payment failed. Please try again.',
                ['retry_url' => $this->urlGenerator->generate(self::ROUTES['payment'], ['id' => $user->getId()])]
            );
        }
    }

    public function validateEmailVerification(User $user): void
    {
        if (!$user->isVerified()) {
            throw new UserAccessException(
                'email_not_verified',
                'Please verify your email address.',
                ['resend_url' => $this->urlGenerator->generate(self::ROUTES['email_verify'])]
            );
        }
    }

    public function validateMembership(User $user): void
    {
        // Skip membership check for super admin
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return;
        }

        if ($this->membershipService->isExpired($user)) {
            throw new UserAccessException(
                'membership_expired',
                'Your annual membership has expired.',
                ['renewal_url' => $this->urlGenerator->generate(self::ROUTES['membership_renew'])]
            );
        }
    }

    public function validateAccountStatus(User $user): void
    {
        $session = $this->requestStack->getSession();

        if ($this->isAccountLocked($session)) {
            throw new UserAccessException(
                'account_locked',
                'Account temporarily locked due to multiple failed attempts.',
                ['unlock_time' => $this->getUnlockTime($session)]
            );
        }

        if ($user->getWaitingSince() !== null) {
            throw new UserAccessException(
                'in_waiting_room',
                'Your account is in the waiting room.',
                ['waiting_room_url' => $this->urlGenerator->generate(self::ROUTES['waiting_room'], ['id' => $user->getId()])]
            );
        }
    }

    /**
     * Access Control Methods
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

    public function getRedirectUrl(UserAccessException $e): string
    {
        return match ($e->getErrorCode()) {
            'pending_payment' => $e->getContext()['payment_url'],
            'payment_failed' => $e->getContext()['retry_url'],
            'email_not_verified' => $e->getContext()['resend_url'],
            'membership_expired' => $e->getContext()['renewal_url'],
            'in_waiting_room' => $e->getContext()['waiting_room_url'],
            'session_expired' => $this->urlGenerator->generate(self::ROUTES['login']),
            default => $this->urlGenerator->generate(self::ROUTES['login']),
        };
    }
}
