<?php

namespace App\Controller\Admin;

use App\Service\KycService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/kyc')]
class KycController extends AbstractController
{
    public function __construct(private readonly KycService $kycService)
    {
    }

    #[Route('/', name: 'admin_kyc_index')]
    public function index(): Response
    {
        return $this->render('admin/kyc/index.html.twig', [
            'verifications' => $this->kycService->getPendingVerifications()
        ]);
    }

    #[Route('/approve/{referenceId}', name: 'admin_kyc_approve', methods: ['POST'])]
    public function approve(Request $request, string $referenceId): Response
    {
        $comment = $request->request->get('comment');
        $this->kycService->approveVerification($referenceId, $comment);
        
        $this->addFlash('success', 'KYC verification approved successfully');
        return $this->redirectToRoute('admin_kyc_index');
    }

    #[Route('/reject/{referenceId}', name: 'admin_kyc_reject', methods: ['POST'])]
    public function reject(Request $request, string $referenceId): Response
    {
        $reason = $request->request->get('reason');
        if (!$reason) {
            $this->addFlash('error', 'Rejection reason is required');
            return $this->redirectToRoute('admin_kyc_index');
        }

        $this->kycService->rejectVerification($referenceId, $reason);
        
        $this->addFlash('success', 'KYC verification rejected');
        return $this->redirectToRoute('admin_kyc_index');
    }
}
