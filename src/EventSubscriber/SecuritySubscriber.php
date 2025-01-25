<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class SecuritySubscriber implements EventSubscriberInterface
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 3600; // 1 hour in seconds
    private const REGISTRATION_EXPIRY_DAYS = 90; // 3 months

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        private readonly UserRepository $userRepository
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1],
            LoginFailureEvent::class => 'onLoginFailure',
            LogoutEvent::class => 'onLogout'
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Clean up expired registrations on login page access
        if ($route === 'app.login') {
            $this->cleanupExpiredRegistrations();
        }
    }

    private function cleanupExpiredRegistrations(): void
    {
        try {
            $expiryDate = new \DateTime("-" . self::REGISTRATION_EXPIRY_DAYS . " days");
            $expiredUsers = $this->userRepository->findByExpiredRegistration($expiryDate);

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

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $email = $request->request->get('_username');
        $session = $request->getSession();

        // Check if account is locked
        $lockedUntil = $session->get('login_locked_until', 0);
        if ($lockedUntil > time()) {
            throw new CustomUserMessageAuthenticationException(
                'Account is locked. Please try again later.'
            );
        }

        // Handle failed attempt
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

        $this->logger->info('Login failure', [
            'email' => $email,
            'ip' => $request->getClientIp(),
            'attempt' => $failedAttempts
        ]);
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
