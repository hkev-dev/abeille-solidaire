<?php

namespace App\Controller\Admin;

use App\Service\KycService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/kyc')]
#[IsGranted('ROLE_ADMIN')]
class KycController extends AbstractController
{
    #[Route('/download/{filename}', name: 'admin_kyc_download')]
    public function downloadKycDocument(string $filename): Response
    {
        $filePath = $this->getParameter('kyc_uploads_dir') . '/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier non trouvé.');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        return $response;
    }
}
