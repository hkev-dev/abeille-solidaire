<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Flower;
use App\Entity\Membership;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly MessageBusInterface $messageBus,
        private readonly string $senderEmail,
        private readonly string $senderName,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $router,
        private readonly string $appSecret
    ) {
    }

    public function sendWelcomeEmail(User $user): void
    {
        $this->queueEmail(
            'emails/registration/welcome.html.twig',
            $user->getEmail(),
            'Welcome to Abeille Solidaire - Complete Your Registration',
            [
                'user' => $user,
                'paymentUrl' => $this->router->generate(
                    'app.registration.payment',
                    ['id' => $user->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'matrixInfo' => [
                    'description' => 'Your position in our 4x4 matrix system will be assigned after payment',
                    'benefits' => [
                        'Automatic placement in matrix system',
                        'Progress through 10 flower levels',
                        'Potential for donations and growth'
                    ]
                ]
            ]
        );
    }

    public function sendMatrixPlacementConfirmation(User $user, int $position, int $depth): void
    {
        $this->queueEmail(
            'emails/registration/matrix_placement.html.twig',
            $user->getEmail(),
            'Your Matrix Position Confirmed - Abeille Solidaire',
            [
                'user' => $user,
                'matrixPosition' => $position,
                'matrixDepth' => $depth,
                'dashboardUrl' => $this->router->generate('app.user.dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]
        );
    }

    public function sendPaymentConfirmation(User $user, string $paymentMethod): void
    {
        $this->queueEmail(
            'emails/registration/payment_confirmed.html.twig',
            $user->getEmail(),
            'Payment Confirmed - Welcome to Abeille Solidaire',
            [
                'user' => $user,
                'paymentMethod' => $paymentMethod,
                'loginUrl' => '/login'
            ]
        );
    }

    public function sendDonationReceipt(User $user, array $receipt): void
    {
        $this->queueEmail(
            'emails/donation/receipt.html.twig',
            $user->getEmail(),
            'Your Donation Receipt - Abeille Solidaire',
            [
                'user' => $user,
                'receipt' => $receipt
            ]
        );
    }

    public function sendPaymentFailureNotification(User $user, ?string $errorMessage): void
    {
        $this->queueEmail(
            'emails/registration/payment_failed.html.twig',
            $user->getEmail(),
            'Payment Failed - Action Required',
            [
                'user' => $user,
                'errorMessage' => $errorMessage,
                'retryUrl' => sprintf('/register/payment/%s', $user->getId())
            ]
        );
    }

    public function sendCommunityWelcome(User $user): void
    {
        $this->queueEmail(
            'emails/registration/community_welcome.html.twig',
            $user->getEmail(),
            'Welcome to Our Community - Next Steps',
            [
                'user' => $user,
                'dashboardUrl' => '/dashboard',
                'guideUrl' => '/guide'
            ]
        );
    }

    public function sendMembershipConfirmation(User $user, Membership $membership): void
    {
        $this->queueEmail(
            'emails/membership/confirmation.html.twig',
            $user->getEmail(),
            'Your Annual Membership Confirmation - Abeille Solidaire',
            [
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
            ]
        );
    }

    public function sendFlowerProgressionEmail(User $user, Flower $newFlower, Flower $previousFlower, float $solidarityAmount): void
    {
        $this->queueEmail(
            'emails/flower/progression.html.twig',
            $user->getEmail(),
            'Congratulations on Your Flower Progression!',
            [
                'user' => $user,
                'flower' => $newFlower,
                'previousFlower' => $previousFlower,
                'solidarityAmount' => $solidarityAmount
            ]
        );
    }

    public function sendMembershipRenewalConfirmation(User $user, Membership $membership): void
    {
        $this->queueEmail(
            'emails/membership/confirmation.html.twig',
            $user->getEmail(),
            'Membership Renewal Confirmation - Abeille Solidaire',
            [
                'user' => $user,
                'membership' => $membership,
                'startDate' => $membership->getStartDate()->format('d/m/Y'),
                'endDate' => $membership->getEndDate()->format('d/m/Y'),
                'amount' => $membership->getAmount(),
                'paymentMethod' => $membership->getStripePaymentIntentId() ? 'card' : 'cryptocurrency',
                'cryptoDetails' => $membership->getCryptoAmount() ? [
                    'amount' => $membership->getCryptoAmount(),
                    'currency' => $membership->getCryptoCurrency()
                ] : null,
                'dashboardUrl' => $this->router->generate('app.user.dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]
        );
    }

    public function sendMembershipRenewalFailure(User $user, string $errorMessage): void
    {
        $this->queueEmail(
            'emails/membership/renewal_failed.html.twig',
            $user->getEmail(),
            'Membership Renewal Failed - Action Required',
            [
                'user' => $user,
                'error' => $errorMessage,
                'retryUrl' => $this->router->generate('app.membership.renew', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]
        );
    }

    public function sendKycSubmissionConfirmation(User $user): void
    {
        $this->queueEmail(
            'emails/kyc/submission_confirmation.html.twig',
            $user->getEmail(),
            'KYC Verification Submission Received',
            [
                'user' => $user,
                'submissionDate' => new \DateTime(),
                'dashboardUrl' => $this->router->generate('app.user.settings.kyc', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]
        );
    }

    public function sendKycApprovalNotification(User $user): void
    {
        $this->queueEmail(
            'emails/kyc/approval_notification.html.twig',
            $user->getEmail(),
            'KYC Verification Approved',
            [
                'user' => $user,
                'approvalDate' => new \DateTime(),
                'walletUrl' => $this->router->generate('app.user.wallet.index', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]
        );
    }

    public function sendKycRejectionNotification(User $user, ?string $reason): void
    {
        $this->queueEmail(
            'emails/kyc/rejection_notification.html.twig',
            $user->getEmail(),
            'KYC Verification Needs Attention',
            [
                'user' => $user,
                'rejectionDate' => new \DateTime(),
                'reason' => $reason,
                'retryUrl' => $this->router->generate('app.user.settings.kyc', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]
        );
    }

    private function queueEmail(string $template, string $recipient, string $subject, array $context): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($recipient)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context(array_merge($context, [
                'unsubscribe_url' => $this->generateUnsubscribeUrl($recipient)
            ]));

        try {
            $this->messageBus->dispatch(new SendEmailMessage($email));

            $this->logger->info('Email queued successfully', [
                'template' => $template,
                'recipient' => $recipient
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to queue email', [
                'template' => $template,
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function generateUnsubscribeUrl(string $email): string
    {
        return $this->router->generate('app_email_unsubscribe', [
            'token' => $this->generateUnsubscribeToken($email)
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function generateUnsubscribeToken(string $email): string
    {
        return hash_hmac('sha256', $email, $this->appSecret);
    }
}
