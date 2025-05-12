<?php

namespace App\Controller\Public;

use App\Entity\User;
use App\Repository\CauseRepository;
use App\Repository\PonctualDonationRepository;
use App\Service\Payment\PaymentFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\Payment\PaymentService;

class PaymentController extends AbstractController
{
    public function __construct(
        private PaymentFactory $paymentFactory,
        private PaymentService $paymentService,
        private CauseRepository $causeRepository,
        private PonctualDonationRepository $ponctualDonationRepository,
    ) {
    }

    #[Route('/payment/registration/{method}', name: 'app.payment.registration')]
    public function createRegistrationPayment(Request $request, string $method): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $includeMembership = $request->query->getBoolean('membership', false);

        try {
            $paymentService = $this->paymentFactory->getPaymentService($method);
            $paymentData = $paymentService->createRegistrationPayment($user, $includeMembership);

            return $this->json([
                'success' => true,
                'data' => $paymentData
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/payment/donation/{method}', name: 'app.payment.donation')]
    public function createDonationPayment(Request $request, string $method): Response
    {
        /** @var User $donor */
        $donor = $this->getUser();
        $recipientId = $request->request->get('recipient_id');
        $amount = $request->request->get('amount');

        try {
            $recipient = $this->em->getRepository(User::class)->find($recipientId);
            if (!$recipient) {
                throw new \Exception('Recipient not found');
            }

            $paymentService = $this->paymentFactory->getPaymentService($method);
            $paymentData = $paymentService->createDonationPayment($donor, $recipient, $amount);

            return $this->json([
                'success' => true,
                'data' => $paymentData
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/webhook/{method}', name: 'app.payment.webhook')]
    public function handleWebhook(Request $request, string $method,LoggerInterface $logger): Response
    {
        try {

            $logger->info("Received $method webhook", [
                'payload' => $request->toArray()
            ]);

            $paymentService = $this->paymentFactory->getPaymentService($method);
            
            $signature = match($method) {
                'stripe' => $request->headers->get('Stripe-Signature'),
                'coinpayments' => $request->headers->get('HMAC'),
                default => throw new \Exception('Unsupported payment method')
            };

            // Verify webhook signature
            if (!$paymentService->verifyPaymentCallback($request->toArray(), $signature)) {
                throw new \Exception('Invalid webhook signature');
            }

            // Process webhook data
            $paymentData = $request->toArray();
            $paymentType = $method === 'stripe' 
                ? ($paymentData['type'] ?? '') 
                : ($paymentData['ipn_type'] ?? '');

            match($paymentType) {
                'payment_intent.succeeded', 'api' => $paymentService->handlePaymentSuccess($paymentData),
                'payment_intent.payment_failed' => $paymentService->handlePaymentFailure($paymentData),
                default => throw new \Exception('Unsupported webhook event')
            };

            return new Response('Webhook processed', Response::HTTP_OK);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/ponctual-donation/payment', name: 'app.pdonation.payment', methods: ['GET', 'POST'])]
    public function paymentSelection(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $user = null;
        }

        // Handle AJAX requests for payment creation
        if ($request->isXmlHttpRequest() && $request->getContent()) {
            $data = json_decode($request->getContent(), true);

            $cause = $this->causeRepository->findOneBy(['slug' => $data['cause']]);

            if (!$cause) {
                return $this->json(['error' => 'Cause not found'], Response::HTTP_BAD_REQUEST);
            }

            $paymentMethod = $data['payment_method'] ?? 'stripe';
            $amount = $data['amount'] ?? 25;

            try {
                $paymentService = $this->paymentFactory->getPaymentService($paymentMethod);
                if (isset($data['currency'])) {
                    $user->setPaymentCurrency($data['currency']);
                }
                $paymentData = $paymentService->createPonctualDonationPayment($user, $cause, $donor = 'Unknown', $amount);
                if (isset($data['currency'])) {
                    $paymentData["currency"] = $data['currency'];
                }

                // Store payment preferences in session
                $request->getSession()->set('payment_method', $paymentMethod);

                if (isset($paymentData['payment_reference']) || isset($paymentData['txn_id'])) {
                    $request->getSession()->set('payment_reference', $paymentData['payment_reference'] ?? $paymentData['txn_id']);
                }

                return $this->json($paymentData);
            } catch (\Exception $e) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        $form = $this->createForm(PaymentSelectionType::class);
        $form->handleRequest($request);

        return $this->render('public/pages/auth/payment-selection.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'stripe_public_key' => $this->getParameter('stripe.public_key'),
        ]);
    }

    #[Route('/ponctual-donation/waiting-room', name: 'app.pdonation.waiting_room')]
    public function waitingRoom(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $user = null;
        }

        $pDonation = $this->ponctualDonationRepository->findOneBy(['id' => $request->query->get('id')]);

        if (!$pDonation) {
            throw $this->createNotFoundException('Donation not found');
        }

        dd($pDonation);

        if ($pDonation->isPaid()) {
            $this->addFlash('success', '❤️ Merci pour votre don ! Votre générosité fait la différence.');
            return $this->redirectToRoute('landing.cause.details', [
                'slug' => $pDonation->getCause()->getSlug(),
            ]);
        } else {
            $this->addFlash('danger', '❌ Le paiement a échoué. Veuillez réessayer ou utiliser un autre moyen de paiement.');
            return $this->redirectToRoute('landing.cause.details', [
                'slug' => $pDonation->getCause()->getSlug()
            ]);
        }

        $paymentMethod = $request->getSession()->get('payment_method', 'stripe');
        $params = [
            'user' => $user,
            'payment_method' => $paymentMethod,
            'payment_url' => $this->generateUrl('landing.cause.details', [
                'slug' => $pDonation->getCause()->getSlug()
            ]),
            'payment_reference' => $request->getSession()->get('payment_reference'),
            'donation' => $pDonation,
        ];

        if ($data = $request->query->get('cp_data')){
            $params['cp_data'] = json_decode($data, true);

            return $this->render('public/pages/auth/coinpayments-waiting-room.html.twig', $params);
        }else if ($paymentMethod === 'stripe'){

            return $this->render('public/pages/cause/waiting-room.html.twig', $params);
        }else{
            $this->addFlash('error', 'Payment method not supported');
            return $this->redirectToRoute('app.user.dashboard');
        }
    }
}