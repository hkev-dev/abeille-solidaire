<?php

namespace App\Controller\User;

use App\Entity\Donation;
use App\Entity\Earning;
use App\Entity\User;
use App\Form\PaymentSelectionType;
use App\Form\PaymentSupplementaryType;
use App\Repository\DonationRepository;
use App\Repository\EarningRepository;
use App\Service\DonationReceiptService;
use App\Service\DonationService;
use App\Service\Payment\PaymentFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Request;

#[Route('/user/donations')]
class DonationController extends AbstractController
{
    public function __construct(
        private readonly PaymentFactory $paymentFactory,
        private readonly DonationRepository     $donationRepository,
        private readonly DonationReceiptService $receiptService,
        private readonly DonationService $donationService,
        private readonly PaginatorInterface     $paginator,
        private readonly EntityManagerInterface $em,
        private readonly EarningRepository $earningRepository,
    )
    {
    }

    #[Route('/received', name: 'app.user.donations.received')]
    public function received(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $query = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Earning::class, 'e')
            ->leftJoin('e.beneficiary', 'beneficiary')
            ->andWhere('beneficiary.donor = :user')
            ->andWhere('beneficiary.paymentStatus = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Donation::PAYMENT_COMPLETED)
            ->orderBy('e.createdAt', 'DESC');

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $stats = [
            'totalReceived' => $this->earningRepository->getTotalReceivedByUser($user),
            'currentFlowerReceived' => $user->getReceivedAmountInCurrentFlower(),
            'flowerProgress' => $user->getFlowerProgress(),
        ];

        return $this->render('user/pages/donations/received.html.twig', [
            'pagination' => $pagination,
            'stats' => $stats
        ]);
    }

    #[Route('/sent', name: 'app.user.donations.sent')]
    public function sent(Request $request): Response
    {
        $user = $this->getUser();
        $query = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Earning::class, 'e')
            ->leftJoin('e.donor', 'donor')
            ->andWhere('donor.donor = :user')
            ->andWhere('donor.paymentStatus = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Donation::PAYMENT_COMPLETED)
            ->orderBy('e.createdAt', 'DESC');

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $stats = [
            'totalSent' => $this->earningRepository->getTotalMadeByUser($user),
            'currentFlowerSent' => $this->donationRepository->findTotalMadeInFlower(
                $user,
                $user->getCurrentFlower()
            ),
        ];

        return $this->render('user/pages/donations/sent.html.twig', [
            'pagination' => $pagination,
            'stats' => $stats
        ]);
    }

    #[Route('/solidarity', name: 'app.user.donations.solidarity')]
    public function solidarity(Request $request): Response
    {
        $user = $this->getUser();
        $query = $this->donationRepository->createQueryBuilder('d')
            ->where('d.recipient = :user')
            ->andWhere('d.donationType = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'solidarity')
            ->orderBy('d.transactionDate', 'DESC');

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $stats = [
            'totalReceived' => $this->donationRepository->findTotalSolidarityReceived($user),
            'totalDistributed' => $this->donationRepository->findTotalSolidarityDistributed($user),
        ];

        return $this->render('user/pages/donations/solidarity.html.twig', [
            'pagination' => $pagination,
            'stats' => $stats
        ]);
    }

    #[Route('/receipt/{id}', name: 'app.user.donations.receipt')]
    public function receipt(int $id, DonationService $donationService): Response
    {
        $donation = $this->donationRepository->find($id);

        if (!$donation || ($donation->getDonor() !== $this->getUser() && !$donationService->isUserBeneficiary($donation, $this->getUser()))) {
            throw $this->createNotFoundException('Donation not found');
        }

        $receipt = $this->receiptService->generateReceipt($donation);

        return $this->render('user/pages/donations/receipt.html.twig', [
            'receipt' => $receipt,
            'donation' => $donation
        ]);
    }

    #[Route('/download-receipt/{id}', name: 'app.user.donations.download_receipt')]
    public function downloadReceipt(int $id,DonationService $donationService): Response
    {
        $donation = $this->donationRepository->find($id);

        if (!$donation || ($donation->getDonor() !== $this->getUser() && !$donationService->isUserBeneficiary($donation, $this->getUser()))) {
            throw $this->createNotFoundException('Donation not found');
        }

        $receipt = $this->receiptService->generateReceipt($donation);

        $html = $this->renderView('user/pages/donations/receipt_pdf.html.twig', [
            'receipt' => $receipt,
            'donation' => $donation
        ]);

        // You'll need to configure a PDF generation service like Dompdf or wkhtmltopdf
        $pdf = /* Generate PDF from HTML */
            null;

        return new Response(
            $pdf,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="receipt-' . $donation->getId() . '.pdf"'
            ]
        );
    }

    #[Route('/make-supplementary', name: 'app.user.donations.make_supplementary', methods: ['GET', 'POST'])]
    public function makeSupplementary(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Handle AJAX payment creation requests
        if ($request->isXmlHttpRequest() && $request->getContent()) {
            return $this->handleAjaxPayment($request, $user);
        }

        // Display payment form
        $form = $this->createForm(PaymentSupplementaryType::class, null, [
            'show_annual_membership' => false,
            'action' => $this->generateUrl('app.user.donations.make_supplementary'),
        ]);

        return $this->render('public/pages/membership/make-supplementary-donation.html.twig', [
            'form' => $form->createView(),
            'amount' => DonationService::SUPPLEMENTARY_FEE,
            'stripe_public_key' => $this->getParameter('stripe.public_key'),
            'user' => $user,
        ]);
    }

    #[Route('/make-supplementary/waiting-room', name: 'app.user.donations.make_supplementary.waiting_room')]
    public function waitingRoom(Request $request, DonationRepository $donationRepository): Response
    {
        $donation = $donationRepository->find($request->query->get('id'));
        if (!$donation) {
            throw $this->createNotFoundException('Donation not found');
        }

        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app.login');
        }

        // If payment failed, redirect back to payment page
        if ($donation->getPaymentStatus() === 'failed') {
            return $this->redirectToRoute('app.user.donations.make_supplementary');
        }

        $paymentMethod = $request->getSession()->get('payment_method', 'stripe');
        $params = [
            'user' => $user,
            'payment_method' => $paymentMethod,
            'payment_url' => $this->generateUrl('app.user.donations.make_supplementary'),
            'payment_reference' => $request->getSession()->get('payment_reference'),
            'donation' => $donation,
            'context' => 'supplementary',
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

    /**
     * SUBSCRIPTION ON SUPPLEMENTARY
     */
    #[Route('/make-sub-pplementary/waiting-room', name: 'app.user.donations.make_sub_supplementary.waiting_room')]
    public function waitingSubRoom(Request $request, DonationRepository $donationRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app.login');
        }

        $paymentMethod = $request->getSession()->get('payment_method', 'stripe');
        $params = [
            'user' => $user,
            'payment_method' => $paymentMethod,
            'payment_url' => $this->generateUrl('app.user.donations.make_supplementary'),
            'payment_reference' => $request->getSession()->get('payment_reference'),
            'context' => 'supplementary',
            'subscriptionId' => $request->get('subscriptionId'),
        ];

        if ($paymentMethod === 'stripe'){
            return $this->render('public/pages/auth/sub-waiting-room.html.twig', $params);
        }else{
            $this->addFlash('error', 'Payment method not supported');
            return $this->redirectToRoute('app.user.dashboard');
        }
    }

    /**
     * Handle AJAX payment creation request
     */
    private function handleAjaxPayment(Request $request, User $user): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $paymentMethod = $data['payment_method'] ?? 'stripe';
            $isMonthly = $data['isMonthly'] ?? false;
            
            if (isset($data['currency'])) {
                $user->setPaymentCurrency($data['currency']);
            }

            $paymentService = $this->paymentFactory->getPaymentService($paymentMethod);

            if ($paymentMethod === 'stripe') {
                if ($isMonthly) {
                    $paymentData = $this->createStripeSubscription($user);
                } else {
                    $paymentData = $paymentService->createSupplementaryDonationPayment($user);
                }
            }

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

    private function createStripeSubscription($user): array
    {
        $stripe = new StripeClient($this->getParameter('stripe.secret_key'));
        #$donation = $this->donationService->createSupplementaryDonation($user);

        if (!$user->getStripeCustomerId()) {
            $customer = $stripe->customers->create([ 'email' => $user->getEmail() ]);
            $user->setStripeCustomerId($customer->id);
            $this->em->persist($user);
            $this->em->flush();
        } else {
            $customer = ['id' => $user->getStripeCustomerId()];
        }

        $priceId = "price_1RQWQWP00kvUrStC3akZhevm";
        $subscription = $stripe->subscriptions->create([
            'customer'         => $customer['id'],
            'items'            => [[ 'price' => $priceId ]],
            'payment_behavior' => 'default_incomplete',
            'expand'           => ['latest_invoice.payment_intent'],
            'metadata'         => [
                'payment_type'   => 'subSupplementary',
            ]
        ]);

        $pi = $subscription->latest_invoice->payment_intent;

        return [
            'subscriptionId' => $subscription->id,
            'clientSecret'   => $pi->client_secret,
            'entityId'       => $subscription->id,
            'userId'         => $user->getId(),
            'paymentIntentId' => $pi->id
        ];
    }
}
