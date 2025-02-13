<?php

namespace App\Controller\User;

use App\Form\KycVerificationType;
use App\Form\PaymentMethodType;
use App\Service\FlowerService;
use App\Service\KycService;
use App\Service\PaymentMethodService;
use App\Service\MembershipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly KycService           $kycService,
    ) {
    }

    #[Route('', name: 'app.user.profile')]
    #[Route('', name: 'app.user.profile.update')]
    public function profile(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(KycVerificationType::class);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    // Validate document type
                    $documentType = $form->get('documentType')->getData();
                    if (!in_array($documentType, ['national_id', 'passport', 'drivers_license', 'residence_permit'])) {
                        throw new \InvalidArgumentException('Type de document invalide');
                    }

                    // Validate required files
                    $files = [
                        'frontImage' => $form['frontImage']->getData(),
                        'backImage' => $form['backImage']->getData(),
                        'selfieImage' => $form['selfieImage']->getData(),
                    ];

                    foreach ($files as $type => $file) {
                        if (!$file) {
                            throw new \InvalidArgumentException("Le fichier $type est requis");
                        }
                    }

                    $formData = $form->getData();
                    $success = $this->kycService->submitVerification($user, $formData, $files);

                    if ($success) {
                        $this->addFlash('success', 'Votre demande de vérification KYC a été soumise avec succès.');
                        return $this->redirectToRoute('app.user.settings.kyc');
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
            }
        }

        return $this->render('user/pages/profile/index.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
}
