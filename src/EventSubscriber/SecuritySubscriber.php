<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\SecurityService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class SecuritySubscriber implements EventSubscriberInterface
{
    private const EXCLUDED_ROUTES = [
        'app.register',
        'app.webhook.coinbase',
        '_wdt', // Web Debug Toolbar
        '_profiler'
    ];

    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 3600; // 1 hour in seconds

    public function __construct(
        private readonly SecurityService $securityService,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1],
            LoginFailureEvent::class => 'onLoginFailure',
            LogoutEvent::class => 'onLogout',
        ];
    }

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
