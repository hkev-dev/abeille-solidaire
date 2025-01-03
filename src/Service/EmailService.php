<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Membership;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $senderEmail,
        private readonly string $senderName,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $router
    ) {
    }

    public function sendWelcomeEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($user->getEmail())
            ->subject('Welcome to Abeilles Solidaires - Complete Your Registration')
            ->htmlTemplate('emails/registration/welcome.html.twig')
            ->context([
                'user' => $user,
                'paymentUrl' => $this->router->generate(
                    'app.registration.payment',
                    ['id' => $user->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send welcome email', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendPaymentConfirmation(User $user, string $paymentMethod): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($user->getEmail())
            ->subject('Payment Confirmed - Welcome to Abeilles Solidaires')
            ->htmlTemplate('emails/registration/payment_confirmed.html.twig')
            ->context([
                'user' => $user,
                'paymentMethod' => $paymentMethod,
                'loginUrl' => '/login'
            ]);

        $this->mailer->send($email);
    }

    public function sendDonationReceipt(User $user, array $receipt): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($user->getEmail())
            ->subject('Your Donation Receipt - Abeilles Solidaires')
            ->htmlTemplate('emails/donation/receipt.html.twig')
            ->context([
                'user' => $user,
                'receipt' => $receipt
            ]);

        $this->mailer->send($email);
    }

    public function sendPaymentFailureNotification(User $user, ?string $errorMessage): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($user->getEmail())
            ->subject('Payment Failed - Action Required')
            ->htmlTemplate('emails/registration/payment_failed.html.twig')
            ->context([
                'user' => $user,
                'errorMessage' => $errorMessage,
                'retryUrl' => sprintf('/register/payment/%s', $user->getId())
            ]);

        $this->mailer->send($email);
    }

    public function sendCommunityWelcome(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($user->getEmail())
            ->subject('Welcome to Our Community - Next Steps')
            ->htmlTemplate('emails/registration/community_welcome.html.twig')
            ->context([
                'user' => $user,
                'dashboardUrl' => '/dashboard',
                'guideUrl' => '/guide'
            ]);

        $this->mailer->send($email);
    }

    public function sendMembershipConfirmation(User $user, Membership $membership): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($user->getEmail())
            ->subject('Your Annual Membership Confirmation - Abeilles Solidaires')
            ->htmlTemplate('emails/membership/confirmation.html.twig')
            ->context([
                'user' => $user,
                'membership' => $membership,
                'startDate' => $membership->getStartDate()->format('d/m/Y'),
                'endDate' => $membership->getEndDate()->format('d/m/Y'),
                'amount' => Membership::ANNUAL_FEE,
                'paymentMethod' => $membership->getStripePaymentIntentId() ? 'card' : 'cryptocurrency',
                'cryptoDetails' => $membership->getCryptoAmount() ? [
                    'amount' => $membership->getCryptoAmount(),
                    'currency' => $membership->getCryptoCurrency()
                ] : null,
                'dashboardUrl' => '#'
            ]);

        try {
            $this->mailer->send($email);

            $this->logger->info('Membership confirmation email sent', [
                'user_id' => $user->getId(),
                'membership_id' => $membership->getId(),
                'email' => $user->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send membership confirmation email', [
                'user_id' => $user->getId(),
                'membership_id' => $membership->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
