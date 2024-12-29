<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class EmailVerificationService
{
    public function __construct(
        private MailerInterface        $mailer,
        private UrlGeneratorInterface  $urlGenerator,
        private EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws RandomException
     */
    public function sendVerificationEmail(User $user): void
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTime('+24 hours');

        $user->setVerificationToken($token);
        $user->setVerificationTokenExpiresAt($expiresAt);

        $verificationUrl = $this->urlGenerator->generate('app.verify_email', [
            'token' => $token
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = new TemplatedEmail()
            ->from('no-reply@qrowd.com')
            ->sender('Qrowd Platform <noreply@qrowd.com>')
            ->to($user->getEmail())
            ->subject('Please verify your email')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'verificationUrl' => $verificationUrl,
                'expiresAt' => $expiresAt,
                'username' => $user->getUsername()
            ]);

        $this->mailer->send($email);
    }
}
