<?php

namespace App\Controller\Public;

use App\Entity\Membership;
use App\Entity\User;
use App\Repository\MembershipRepository;
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
        /** @var User $user */
        $user = $this->getUser();

        // Direct check using membership service
        if ($user->hasPaidAnnualFee()) {
            return $this->json(['status' => 'active']);
        }

        return $this->json([
            'status' => 'expired',
            'message' => 'Annual membership has expired.',
            'renewal_url' => $this->generateUrl('app.membership.renew')
        ]);
    }

    #[Route('/membership/renew', name: 'app.membership.renew', methods: ['GET', 'POST'])]
    public function renew(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Only redirect to dashboard if membership is not expired
        if (!$this->membershipService->isExpired($user)) {
            return $this->redirectToRoute('app.user.dashboard');
        }

        // Handle AJAX payment creation requests
        if ($request->isXmlHttpRequest() && $request->getContent()) {
            return $this->handleAjaxPayment($request, $user);
        }

        // Display payment form
        $form = $this->createForm(PaymentSelectionType::class, null, [
            'show_annual_membership' => false,
            'action' => $this->generateUrl('app.membership.renew'),
        ]);

        return $this->render('public/pages/membership/renew.html.twig', [
            'form' => $form->createView(),
            'amount' => MembershipService::MEMBERSHIP_FEE,
            'stripe_public_key' => $this->getParameter('stripe.public_key'),
            'user' => $user,
        ]);
    }

    /**
     * Handle AJAX payment creation request
     */
    private function handleAjaxPayment(Request $request, User $user): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $paymentMethod = $data['payment_method'] ?? 'stripe';

            $paymentService = $this->paymentFactory->getPaymentService($paymentMethod);

            if (isset($data['currency'])) {
                $user->setPaymentCurrency($data['currency']);
            }
            $paymentData = $paymentService->createMembershipPayment($user);
            if (isset($data['currency'])) {
                $paymentData["currency"] = $data['currency'];
            }

            // Store payment info in session
            $session = $request->getSession();
            $session->set('payment_method', $paymentMethod);
            if (isset($paymentData['payment_reference']) || isset($paymentData['txn_id'])) {
                $request->getSession()->set('payment_reference', $paymentData['payment_reference'] ?? $paymentData['txn_id']);
            }

            return $this->json($paymentData);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }
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
    public function waitingRoom(Request $request, MembershipRepository $membershipRepository): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app.login');
        }

        $membership = $membershipRepository->find($request->query->get('id'));

        if (!$membership) {
            throw $this->createNotFoundException('Membership not found');
        }

        if (!$this->membershipService->isExpired($user)) {
            return $this->redirectToRoute('app.user.dashboard');
        }

        // If payment failed, redirect back to payment page
        if ($membership->getPaymentStatus() === 'failed') {
            return $this->redirectToRoute('app.membership.renew');
        }

        $paymentMethod = $request->getSession()->get('payment_method', 'stripe');
        $params = [
            'user' => $user,
            'payment_method' => $paymentMethod,
            'payment_url' => $this->generateUrl('app.membership.renew'),
            'payment_reference' => $request->getSession()->get('payment_reference'),
            'membership' => $membership,
            'context' => 'membership',
        ];

        if ($data = $request->query->get('cp_data')){
            $params['cp_data'] = json_decode($data, true);

            return $this->render('public/pages/auth/coinpayments-waiting-room.html.twig', $params);
        }else if ($paymentMethod === 'stripe'){

            return $this->render('public/pages/auth/waiting-room.html.twig', $params);
        }else{
            $this->addFlash('error', 'Payment method not supported');
            return $this->redirectToRoute('app.user.dashboard');
        }

    }

    #[Route('/membership/check-payment/{id}', name: 'app.membership.check_payment')]
    public function checkPayment(Request $request, Membership $membership): Response
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
