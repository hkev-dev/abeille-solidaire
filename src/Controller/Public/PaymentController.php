<?php

namespace App\Controller\Public;

use App\Entity\User;
use App\Service\Payment\PaymentFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    public function __construct(
        private PaymentFactory $paymentFactory
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
}