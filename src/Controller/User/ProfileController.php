<?php

namespace App\Controller\User;

use App\Form\ChangePasswordType;
use App\Service\KycService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{

    #[Route('', name: 'app.user.profile')]
    public function profile(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $currentPassword = $form->get('currentPassword')->getData();
                    $newPassword = $form->get('newPassword')->getData();

                    if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                        $this->addFlash('danger', 'Mot de passe actuel incorrect');

                        return $this->redirectToRoute('app.user.profile');
                    }

                    // Mettre à jour le mot de passe
                    $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($hashedPassword);

                    $entityManager->persist($user);
                    $entityManager->flush();

                    $this->addFlash('success', 'Mot de passe mis à jour avec succès');

                    return $this->redirectToRoute('app.user.profile');
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
