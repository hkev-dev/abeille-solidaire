<?php

namespace App\EventSubscriber;

use App\Event\MembershipExpiredEvent;
use App\Event\MembershipActivatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MembershipEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly MailerInterface $mailer
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MembershipExpiredEvent::NAME => 'onMembershipExpired',
            MembershipActivatedEvent::NAME => 'onMembershipActivated'
        ];
    }

    public function onMembershipExpired(MembershipExpiredEvent $event): void
    {
        $membership = $event->getMembership();
        $user = $membership->getUser();

        // Log the expiration
        $this->logger->info(sprintf(
            'Membership expired for user %s (ID: %d)',
            $user->getEmail(),
            $user->getId()
        ));

        // Send expiration notification email
        $this->sendExpirationEmail($user);

        // If the user was in an active flower progression, we could handle that here
        // For now, the flower/matrix services will check membership status during operations
    }

    public function onMembershipActivated(MembershipActivatedEvent $event): void
    {
        $membership = $event->getMembership();
        $user = $membership->getUser();

        // Log the activation
        $this->logger->info(sprintf(
            'Membership activated for user %s (ID: %d)',
            $user->getEmail(),
            $user->getId()
        ));

        // Send welcome/confirmation email
        $this->sendActivationEmail($user);
    }

    private function sendExpirationEmail($user): void
    {
        try {
            $email = (new Email())
                ->from('no-reply@abeilleolidaire.com')
                ->to($user->getEmail())
                ->subject('Your Abeille Solidaire Membership Has Expired')
                ->html(sprintf(
                    'Dear %s,<br><br>
                    Your Abeille Solidaire membership has expired. To continue participating 
                    in flower progressions and matrix activities, please renew your membership.<br><br>
                    You can renew your membership by logging into your account.<br><br>
                    Best regards,<br>
                    Abeille Solidaire Team',
                    $user->getFirstName()
                ));

            $this->mailer->send($email);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send membership expiration email', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendActivationEmail($user): void
    {
        try {
            $email = (new Email())
                ->from('no-reply@abeilleolidaire.com')
                ->to($user->getEmail())
                ->subject('Your Abeille Solidaire Membership is Active')
                ->html(sprintf(
                    'Dear %s,<br><br>
                    Your Abeille Solidaire membership is now active. You can now fully participate 
                    in flower progressions and matrix activities.<br><br>
                    Thank you for being part of our community!<br><br>
                    Best regards,<br>
                    Abeille Solidaire Team',
                    $user->getFirstName()
                ));

            $this->mailer->send($email);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send membership activation email', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
