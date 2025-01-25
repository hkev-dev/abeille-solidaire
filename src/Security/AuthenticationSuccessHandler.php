<?php

namespace App\Security;

use App\Entity\User;
use App\Service\SecurityService;
use App\Exception\UserAccessException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly SecurityService $securityService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return new RedirectResponse($this->urlGenerator->generate('app.login'));
        }

        // Skip all redirects for super admin
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return new RedirectResponse($this->urlGenerator->generate('app.user.dashboard'));
        }

        try {
            // Validate user status
            $this->securityService->validateUserStatus($user);
            return new RedirectResponse($this->urlGenerator->generate('app.user.dashboard'));

        } catch (UserAccessException $e) {
            // Handle specific redirect based on the exception
            $redirectRoute = match ($e->getErrorCode()) {
                'pending_payment' => 'app.registration.payment',
                'in_waiting_room' => 'app.waiting_room',
                'membership_expired' => 'app.membership.renew',
                default => 'app.login'
            };

            $this->logger->info('Redirecting authenticated user based on status', [
                'user_id' => $user->getId(),
                'status' => $e->getErrorCode(),
                'redirect_to' => $redirectRoute
            ]);

            return new RedirectResponse($this->urlGenerator->generate($redirectRoute));
        }
    }
}