<?php

namespace App\Controller\Public;

use App\DTO\RegistrationDTO;
use App\Entity\User;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app.login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('landing.home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('public/pages/auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/logout', name: 'app.logout', methods: ['GET'])]
    public function logout(): void
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws RandomException
     */
    #[Route('/register', name: 'app.register')]
    public function register(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface      $entityManager,
        EmailVerificationService    $emailVerificationService
    ): Response
    {
        $dto = new RegistrationDTO();
        $form = $this->createForm(RegistrationType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = new User();
            $user->setEmail($dto->email);
            $user->setUsername($dto->username);
            $user->setPassword($passwordHasher->hashPassword($user, $dto->password));
            $user->setIsVerified(false); // Explicitly set false

            // First persist to get the ID
            $entityManager->persist($user);
            $entityManager->flush();

            // Send verification email
            $emailVerificationService->sendVerificationEmail($user);
            
            // Flush again after email service sets the tokens
            $entityManager->flush();

            $this->addFlash('success', 'Registration successful! We have sent you an email with verification instructions.');
            return $this->redirectToRoute('app.login');
        }

        return $this->render('public/pages/auth/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/verify-email/{token}', name: 'app.verify_email')]
    public function verifyEmail(
        string                 $token,
        EntityManagerInterface $entityManager,
        UserRepository         $userRepository
    ): Response
    {
        $user = $userRepository->findOneBy(['verificationToken' => $token]);

        if (!$user || $user->getVerificationTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'The verification link is invalid or has expired. Please request a new one.');
            return $this->redirectToRoute('app.login');
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);

        $entityManager->flush();

        $this->addFlash('success', 'Your email has been verified successfully! You can now log in to your account.');
        return $this->redirectToRoute('app.login');
    }
}
