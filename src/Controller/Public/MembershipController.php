<?php

namespace App\Controller\Public;

use App\Service\SecurityService;
use App\Form\PaymentSelectionType;
use App\Service\MembershipService;
use App\Exception\UserAccessException;
use App\Service\RegistrationPaymentService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER')]
class MembershipController extends AbstractController
{
    public function __construct(
        private readonly MembershipService $membershipService,
        private readonly RegistrationPaymentService $paymentService,
        private readonly SecurityService $securityService
    ) {
    }

    #[Route('/membership/status', name: 'app.membership.status', methods: ['GET'])]
    public function status(): Response
    {
        $user = $this->getUser();

        try {
            $this->securityService->validateMembership($user);
            return $this->json(['status' => 'active']);
        } catch (UserAccessException $e) {
            return $this->json([
                'status' => 'expired',
                'message' => $e->getMessage(),
                'renewal_url' => $this->generateUrl('app.membership.renew')
            ]);
        }
    }

    #[Route('/membership/renew', name: 'app.membership.renew', methods: ['GET', 'POST'])]
    public function renew(Request $request): Response
    {
        // Handle AJAX requests for Stripe payment intent creation
        if ($request->isXmlHttpRequest() && $request->getContent()) {
            $data = json_decode($request->getContent(), true);

            if ($data['payment_method'] === 'stripe') {
                try {
                    $stripeData = $this->paymentService->createStripePaymentIntent(
                        $this->getUser(),
                        'membership_renewal'
                    );
                    $request->getSession()->set('payment_method', 'stripe');
                    return $this->json(['clientSecret' => $stripeData['clientSecret']]);
                } catch (\Exception $e) {
                    return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
                }
            }
        }

        $form = $this->createForm(PaymentSelectionType::class);
        $form->handleRequest($request);

        return $this->render('public/pages/membership/renew.html.twig', [
            'form' => $form->createView(),
            'amount' => $this->membershipService->getRenewalAmount(),
            'stripe_public_key' => $this->getParameter('stripe.public_key'),
        ]);
    }

    #[Route('/membership/renew/crypto', name: 'app.membership.renew.crypto', methods: ['POST'])]
    public function renewWithCrypto(Request $request): Response
    {
        try {
            $user = $this->getUser();

            $transaction = $this->paymentService->createCoinPaymentsTransaction(
                $user,
                'membership_renewal'
            );

            $request->getSession()->set('payment_method', 'crypto');
            $request->getSession()->set('txn_id', $transaction['txn_id']);

            return $this->json($transaction);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/membership/check-payment-status', name: 'app.membership.check_payment_status')]
    public function checkPaymentStatus(): Response
    {
        $user = $this->getUser();
        $membership = $this->membershipService->getLatestMembership($user);

        if ($membership && !$this->membershipService->isExpired($user)) {
            return $this->json([
                'status' => 'completed',
                'redirect' => $this->generateUrl('landing.home')
            ]);
        }

        return $this->json([
            'status' => 'pending'
        ]);
    }
}
