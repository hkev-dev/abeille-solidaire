<?php

namespace App\Controller\User;

use App\Form\KycVerificationType;
use App\Form\PaymentMethodType;
use App\Service\KycService;
use App\Service\PaymentMethodService;
use App\Service\MembershipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/user/settings')]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly KycService $kycService,
        private readonly PaymentMethodService $paymentMethodService,
        private readonly MembershipService $membershipService,
    ) {
    }

    #[Route('/kyc', name: 'app.user.settings.kyc')]
    public function kyc(Request $request): Response
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

        return $this->render('user/pages/settings/kyc.html.twig', [
            'user' => $user,
            'form' => $form,
            'kycStatus' => $this->kycService->getKycStatus($user)
        ]);
    }

    #[Route('/payment-methods', name: 'app.user.settings.payment_methods')]
    public function paymentMethods(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(PaymentMethodType::class);

        return $this->render('user/pages/settings/payment-methods.html.twig', [
            'paymentMethods' => $this->paymentMethodService->getUserPaymentMethods($user),
            'form' => $form,
            'supportedCryptos' => $this->paymentMethodService->getSupportedCryptoCurrencies(),
        ]);
    }

    #[Route('/membership', name: 'app.user.settings.membership')]
    public function membership(): Response
    {
        $user = $this->getUser();

        return $this->render('user/pages/settings/membership.html.twig', [
            'currentMembership' => $user->getCurrentMembership(),
            'membershipHistory' => $this->membershipService->getMembershipHistory($user),
            'renewalAmount' => $this->membershipService->getRenewalAmount(),
        ]);
    }
}
