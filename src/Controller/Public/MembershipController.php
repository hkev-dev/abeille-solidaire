<?php

namespace App\Controller\Public;

use App\Service\SecurityService;
use App\Form\PaymentSelectionType;
use App\Service\MembershipService;
use App\Exception\UserAccessException;
use App\Service\Payment\PaymentFactory;
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
        private readonly PaymentFactory $paymentFactory,
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
        $user = $this->getUser();

        // Handle AJAX requests for payment creation
        if ($request->isXmlHttpRequest() && $request->getContent()) {
            $data = json_decode($request->getContent(), true);
            $paymentMethod = $data['payment_method'] ?? 'stripe';

            try {
                $paymentService = $this->paymentFactory->getPaymentService($paymentMethod);
                $paymentData = $paymentService->createMembershipPayment($user);

                // Store payment method in session
                $request->getSession()->set('payment_method', $paymentMethod);
                if (isset($paymentData['txn_id'])) {
                    $request->getSession()->set('txn_id', $paymentData['txn_id']);
                }

                return $this->json($paymentData);
            } catch (\Exception $e) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        $form = $this->createForm(PaymentSelectionType::class, null, [
            'show_annual_membership' => false, // Hide annual membership checkbox for renewal
            'action' => $this->generateUrl('app.membership.renew', ['id' => $user->getId()]),
        ]);
        $form->handleRequest($request);

        return $this->render('public/pages/membership/renew.html.twig', [
            'form' => $form->createView(),
            'amount' => $this->membershipService->getRenewalAmount(),
            'stripe_public_key' => $this->getParameter('stripe.public_key'),
        ]);
    }

    #[Route('/membership/renew/crypto', name: 'app.membership..crypto', methods: ['POST'])]
    public function renewWithCrypto(Request $request): Response
    {
        try {
            $user = $this->getUser();

            $transaction = $this->coinPaymentsService->createCoinPaymentsTransaction(
                $user,
                'BTC',
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
                'redirect' => $this->generateUrl('app.user.dashboard')
            ]);
        }

        return $this->json([
            'status' => 'pending'
        ]);
    }

    #[Route('/membership/waiting-room', name: 'app.membership.waiting_room')]
    public function waitingRoom(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app.login');
        }

        if ($user->hasPaidAnnualFee()) {
            return $this->redirectToRoute('app.user.dashboard');
        }

        // If payment failed, redirect back to payment page
        if ($user->getRegistrationPaymentStatus() === 'failed') {
            return $this->redirectToRoute('app.membership.renew');
        }

        $paymentMethod = $request->getSession()->get('payment_method', 'stripe');

        return $this->render('public/pages/auth/waiting-room.html.twig', [
            'user' => $user,
            'payment_method' => $paymentMethod,
            'payment_url' => $this->generateUrl('app.membership.renew'),
            'txn_id' => $request->getSession()->get('txn_id'),
            'context' => 'membership' // Add this to differentiate from registration in template
        ]);
    }

    #[Route('/membership/check-payment', name: 'app.membership.check_payment')]
    public function checkPayment(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json([
                'status' => 'error',
                'redirect' => $this->generateUrl('app.login')
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->hasPaidAnnualFee()) {
            return $this->json([
                'status' => 'completed',
                'redirect' => $this->generateUrl('app.user.dashboard')
            ]);
        }

        // Get latest membership payment status
        $lastMembership = $this->membershipService->getLatestMembership($user);
        $status = $lastMembership ? $lastMembership->getStatus() : 'pending';

        return $this->json([
            'status' => $status,
            'days_remaining' => $user->getDaysUntilAnnualFeeExpiration()
        ]);
    }
}
