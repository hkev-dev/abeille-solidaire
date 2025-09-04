<?php

namespace App\Security;

use App\Entity\User;
use App\Service\MembershipService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly MembershipService $membershipService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        /** @var User $user */
        $user = $token->getUser();
        
        if (!$user->isActive()) {
            $request->getSession()->invalidate();
            $this->container->get('security.token_storage')->setToken(null);
        
            return new RedirectResponse($this->urlGenerator->generate('app.login'));
        }

        if (!$user instanceof User) {
            return new RedirectResponse($this->urlGenerator->generate('app.login'));
        }

        if (empty(array_diff(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles()))) {
            $request->getSession()->set('justLoggedIn', true);
        }

        // Skip all checks for super admin
        if (array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $user->getRoles())) {
            return new RedirectResponse($this->urlGenerator->generate('app.admin.dashboard'));
        }

        try {
            return $this->determineRedirect($user);
        } catch (\Exception $e) {
            $this->logger->error('Authentication redirect failed', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            return new RedirectResponse($this->urlGenerator->generate('app.login'));
        }
    }

    private function determineRedirect(User $user): RedirectResponse
    {
        // Check registration payment status
        if (!$user->getMainDonation() || $user->getMainDonation()->getPaymentStatus() !== 'completed') {
            return new RedirectResponse($this->urlGenerator->generate('app.registration.payment'));
        }

        // Check if user is in waiting room
        if ($user->getWaitingSince() !== null) {
            return new RedirectResponse($this->urlGenerator->generate('app.waiting_room'));
        }

        // Check membership status
        if (!$user->hasPaidAnnualFee() || $this->membershipService->isExpired($user)) {
            return new RedirectResponse($this->urlGenerator->generate('app.membership.renew'));
        }

        // All essential checks passed, go to dashboard
        return new RedirectResponse($this->urlGenerator->generate('app.user.dashboard'));
    }
}