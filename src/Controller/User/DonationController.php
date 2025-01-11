<?php

namespace App\Controller\User;

use App\Repository\DonationRepository;
use App\Service\DonationReceiptService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

#[Route('/user/donations')]
class DonationController extends AbstractController
{
    public function __construct(
        private readonly DonationRepository $donationRepository,
        private readonly DonationReceiptService $receiptService,
        private readonly PaginatorInterface $paginator,
    ) {
    }

    #[Route('/received', name: 'app.user.donations.received')]
    public function received(Request $request): Response
    {
        $user = $this->getUser();
        $query = $this->donationRepository->createQueryBuilder('d')
            ->where('d.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('d.transactionDate', 'DESC');

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $stats = [
            'totalReceived' => $this->donationRepository->getTotalReceivedByUser($user),
            'currentFlowerReceived' => $this->donationRepository->getTotalReceivedInFlower(
                $user,
                $user->getCurrentFlower()
            ),
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
        $query = $this->donationRepository->createQueryBuilder('d')
            ->where('d.donor = :user')
            ->setParameter('user', $user)
            ->orderBy('d.transactionDate', 'DESC');

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $stats = [
            'totalSent' => $this->donationRepository->getTotalMadeByUser($user),
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
    public function receipt(int $id): Response
    {
        $donation = $this->donationRepository->find($id);
        
        if (!$donation || 
            ($donation->getDonor() !== $this->getUser() && 
             $donation->getRecipient() !== $this->getUser())) {
            throw $this->createNotFoundException('Donation not found');
        }

        $receipt = $this->receiptService->generateReceipt($donation);

        return $this->render('user/pages/donations/receipt.html.twig', [
            'receipt' => $receipt,
            'donation' => $donation
        ]);
    }

    #[Route('/download-receipt/{id}', name: 'app.user.donations.download_receipt')]
    public function downloadReceipt(int $id): Response
    {
        $donation = $this->donationRepository->find($id);
        
        if (!$donation || 
            ($donation->getDonor() !== $this->getUser() && 
             $donation->getRecipient() !== $this->getUser())) {
            throw $this->createNotFoundException('Donation not found');
        }

        $receipt = $this->receiptService->generateReceipt($donation);
        
        $html = $this->renderView('user/pages/donations/receipt_pdf.html.twig', [
            'receipt' => $receipt,
            'donation' => $donation
        ]);

        // You'll need to configure a PDF generation service like Dompdf or wkhtmltopdf
        $pdf = /* Generate PDF from HTML */null;

        return new Response(
            $pdf,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="receipt-' . $donation->getId() . '.pdf"'
            ]
        );
    }
}
