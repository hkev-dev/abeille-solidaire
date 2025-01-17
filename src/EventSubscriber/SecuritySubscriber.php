<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use App\Service\SecurityService;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class SecuritySubscriber implements EventSubscriberInterface
{
    private const EXCLUDED_ROUTES = [
        'app.register',
        'app.webhook.coinbase',
        'app.registration.payment', // Updated route name
        'app.waiting_room',
        '_wdt', // Web Debug Toolbar
        '_profiler'
    ];

    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 3600; // 1 hour in seconds
    private const REGISTRATION_EXPIRY_DAYS = 90; // 3 months

    public function __construct(
        private readonly SecurityService $securityService,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        private readonly UserRepository $userRepository,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1],
            LoginFailureEvent::class => 'onLoginFailure',
            LogoutEvent::class => 'onLogout',
            'security.authentication.success' => ['onAuthenticationSuccess', -10],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    private ?RedirectResponse $pendingRedirect = null;

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Skip excluded routes
        if (in_array($route, self::EXCLUDED_ROUTES)) {
            return;
        }

        try {
            // Clean up expired registrations
            if ($route === 'app.login') {
                $this->cleanupExpiredRegistrations();
            }

            if ($request->isMethod('POST')) {
                // Only check registration throttle for registration route
                if ($route === 'app.register') {
                    $this->securityService->checkRegistrationThrottle();
                    return; // Skip additional checks for registration
                }

                // Verify reCAPTCHA only for login
                if ($route === 'app.login') {
                    $recaptchaResponse = $request->request->get('g-recaptcha-response');
                    if (!$recaptchaResponse || !$this->securityService->verifyRecaptcha($recaptchaResponse)) {
                        throw new CustomUserMessageAuthenticationException('Invalid reCAPTCHA. Please try again.');
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Security check failed', [
                'route' => $route,
                'ip' => $request->getClientIp(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Skip all redirects for super admin
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $this->logger->info('Super admin authenticated successfully', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);
            return;
        }

        // Check user status and set pending redirect
        try {
            if ($this->isRegistrationExpired($user)) {
                throw new CustomUserMessageAuthenticationException('Your registration has expired. Please register again.');
            }

            if (!$user->isVerified()) {
                if ($user->getRegistrationPaymentStatus() === 'pending') {
                    // Updated to use correct route with required user ID parameter
                    $this->pendingRedirect = new RedirectResponse(
                        $this->urlGenerator->generate('app.registration.payment', ['id' => $user->getId()])
                    );
                    return;
                }
            }

            if ($user->getWaitingSince() !== null) {
                $this->pendingRedirect = new RedirectResponse($this->urlGenerator->generate('app.waiting_room'));
                return;
            }

            // Check annual membership status
            if ($this->securityService->isAnnualMembershipExpired($user)) {
                $this->pendingRedirect = new RedirectResponse($this->urlGenerator->generate('app.membership.renew'));
                return;
            }

            $this->logger->info('User authenticated successfully', [
                'user_id' => $user->getId(),
                'status' => [
                    'verified' => $user->isVerified(),
                    'payment_status' => $user->getRegistrationPaymentStatus(),
                    'waiting_since' => $user->getWaitingSince()?->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Authentication status check failed', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($this->pendingRedirect) {
            $event->setResponse($this->pendingRedirect);
        }
    }

    private function cleanupExpiredRegistrations(): void
    {
        try {
            $expiryDate = new \DateTime("-" . self::REGISTRATION_EXPIRY_DAYS . " days");
            $expiredUsers = $this->userRepository->findExpiredRegistrations($expiryDate);

            foreach ($expiredUsers as $user) {
                $this->logger->info('Removing expired registration', [
                    'user_id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'waiting_since' => $user->getWaitingSince()?->format('Y-m-d H:i:s')
                ]);
                $this->userRepository->remove($user, true);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to cleanup expired registrations', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function isRegistrationExpired(User $user): bool
    {
        if (!$user->isVerified() && $user->getWaitingSince() !== null) {
            $expiryDate = new \DateTime("-" . self::REGISTRATION_EXPIRY_DAYS . " days");
            return $user->getWaitingSince() < $expiryDate;
        }
        return false;
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $email = $request->request->get('_username');

        $this->logger->info('Login failure', [
            'email' => $email,
            'ip' => $request->getClientIp()
        ]);

        // Store failed attempt in session
        $session = $request->getSession();
        $failedAttempts = $session->get('failed_login_attempts', 0) + 1;
        $session->set('failed_login_attempts', $failedAttempts);

        if ($failedAttempts >= self::MAX_LOGIN_ATTEMPTS) {
            $session->set('login_locked_until', time() + self::LOCKOUT_DURATION);
            
            $this->logger->alert('Account locked due to multiple failed login attempts', [
                'email' => $email,
                'ip' => $request->getClientIp()
            ]);

            throw new CustomUserMessageAuthenticationException(
                'Too many failed login attempts. Please try again in 1 hour.'
            );
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        // Clear security-related session data
        $session->remove('failed_login_attempts');
        $session->remove('login_locked_until');

        /** @var User|null $user */
        $user = $event->getToken()?->getUser();
        if ($user instanceof User) {
            $this->logger->info('User logged out', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);
        }
    }
}
