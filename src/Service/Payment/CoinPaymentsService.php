<?php

namespace App\Service\Payment;

use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Donation;
use App\Service\MembershipService;
use CoinpaymentsAPI;
use DateMalformedStringException;
use Exception;
use Psr\Log\LoggerInterface;
use App\Service\MatrixService;
use App\Service\DonationService;
use App\Exception\WebhookException;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CoinPaymentsService extends AbstractPaymentService
{
    const PAYMENT_PROVIDER = 'coinpayments';

    protected $coinPayments;
    protected $router;

    public function __construct(
        EntityManagerInterface $em,
        MatrixService $matrixService,
        DonationService $donationService,
        LoggerInterface $logger,
        ParameterBagInterface $params,
        RouterInterface $router,
        MembershipService $membershipService
    ) {
        parent::__construct($em, $matrixService, $donationService, $logger, $params, $membershipService);
        $this->router = $router;
        
        $this->coinPayments = new CoinpaymentsAPI(
            $this->params->get('coinpayments.private_key'),
            $this->params->get('coinpayments.public_key'),
            'json'
        );
    }

    public function createRegistrationPayment(User $user, bool $includeMembership): array
    {
        $amount = $includeMembership ? 50.00 : 25.00;

        $donation = $this->donationService->createRegistrationDonation($user);

        try {
            $result = $this->coinPayments->CreateComplexTransaction(
                amount: $amount,
                currency1: 'EUR',
                currency2: 'BTC',
                buyer_email: $user->getEmail(),
                address: "",
                buyer_name: $user->getFullName(),
                item_name: $includeMembership ? 'Registration + Annual Membership' : 'Registration',
                item_number: "_{$user->getId()}",
                invoice: "INV" . '-' . $user->getId(),
                custom: json_encode([
                    'include_membership' => $includeMembership,
                    'donation_id' => $donation->getId(),
                    'payment_type' => 'registration'
                ]),
                ipn_url: $this->router->generate('_webhook_controller', ['type' => self::PAYMENT_PROVIDER], UrlGeneratorInterface::ABSOLUTE_URL)
            );

            if ($result['error'] !== 'ok') {
                throw new RuntimeException($result['error']);
            }

            $result['result']['entityId'] = $donation->getId();
            return $result['result'];
        } catch (Exception $e) {
            $this->logger->error('CoinPayments transaction creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            throw $e;
        }
    }

    public function createMembershipPayment(User $user): array
    {
        try {
            $membership = $this->membershipService->createMembership($user);

            $result = $this->coinPayments->CreateComplexTransaction(
                amount: 25.00,
                currency1: 'EUR',
                currency2: 'BTC',
                buyer_email: $user->getEmail(),
                address: "",
                buyer_name: $user->getFullName(),
                item_name: 'Annual Membership Renewal',
                item_number: "M_{$user->getId()}",
                invoice: "MEM-" . uniqid(),
                custom: json_encode([
                    'membership_id' => $membership->getId(),
                    'payment_type' => 'membership'
                ]),
                ipn_url: $this->router->generate('_webhook_controller', ['type' => self::PAYMENT_PROVIDER], UrlGeneratorInterface::ABSOLUTE_URL)
            );

            if ($result['error'] !== 'ok') {
                throw new RuntimeException($result['error']);
            }

            $result['result']['entityId'] = $membership->getId();
            return $result['result'];
        } catch (Exception $e) {
            $this->logger->error('CoinPayments membership transaction creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            throw $e;
        }
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function handlePaymentSuccess(array $paymentData): PayableInterface
    {
        $customData = json_decode($paymentData['custom'], true);
        if ($customData['payment_type'] === self::PAYMENT_TYPE_MEMBERSHIP) {
            $payableObject = $membership = $this->em->getRepository(Membership::class)->find($customData['membership_id']);

            if (!$membership) {
                throw new Exception('Membership not found');
            }

            $this->processMembershipPayment($membership, $paymentData['txn_id']);
        }else{
            $payableObject = $donation = $this->em->getRepository(Donation::class)->find($customData['donation_id']);

            if (!$donation) {
                throw new Exception('Donation not found');
            }

            $this->processPaymentType($donation, $customData['payment_type'], $paymentData['txn_id'], $customData['include_membership'] ?? false);
        }

        return $payableObject;
    }

    public function handlePaymentFailure(array $paymentData): void
    {
        $customData = json_decode($paymentData['custom'], true);
        $user = $this->em->getRepository(User::class)->find($customData['user_id']);

        if ($user && $user->getMainDonation()) {
            $user->getMainDonation()->setPaymentStatus('failed');
            $this->em->flush();
        }
    }

    public function verifyPaymentCallback(array $data, string $hmac): bool
    {
        if (!isset($data['ipn_mode'], $data['merchant'])) {
            throw new WebhookException('Invalid IPN data format');
        }

        if ($data['ipn_mode'] !== 'hmac') {
            throw new WebhookException('Invalid IPN mode');
        }

        $merchantId = $this->params->get('coinpayments.merchant_id');
        if ($data['merchant'] !== $merchantId) {
            throw new WebhookException('Invalid merchant ID');
        }

        $calculatedHmac = hash_hmac(
            'sha512',
            file_get_contents('php://input'),
            $this->params->get('coinpayments.ipn_secret')
        );

        return hash_equals($calculatedHmac, $hmac);
    }

    public function getAcceptedCryptoCurrencies(): array
    {
        try {
            $result = $this->coinPayments->GetRates();

            if ($result['error'] !== 'ok') {
                throw new Exception($result['error'] ?? 'Failed to fetch rates');
            }

            $accepted = [];
            foreach ($result['result'] as $coin => $data) {
                if (!is_array($data)) continue;

                $isAccepted = $data['accepted'] ?? true;
                if ($isAccepted) {
                    $accepted[$coin] = [
                        'name' => $data['name'] ?? $coin,
                        'rate_btc' => $data['rate_btc'] ?? '0',
                        'tx_fee' => $data['tx_fee'] ?? '0',
                        'confirms_needed' => $data['confirms'] ?? 3,
                        'is_fiat' => $data['is_fiat'] ?? 0
                    ];
                }
            }

            if ($this->params->get('kernel.environment') !== 'prod') {
                $accepted['LTCT'] = [
                    'name' => 'Litecoin Testnet',
                    'rate_btc' => '0.00000000',
                    'tx_fee' => '0.00000000',
                    'confirms_needed' => 3,
                    'is_fiat' => 0
                ];
            }

            return $accepted;
        } catch (Exception $e) {
            $this->logger->error('Failed to fetch accepted cryptocurrencies', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public static function getProvider(): string
    {
        return self::PAYMENT_PROVIDER;
    }

    public function createSupplementaryDonationPayment(User $user): array
    {
        $amount = DonationService::SUPPLEMENTARY_FEE;

        $donation = $this->donationService->createSupplementaryDonation($user);

        try {
            $result = $this->coinPayments->CreateComplexTransaction(
                amount: $amount,
                currency1: 'EUR',
                currency2: 'BTC',
                buyer_email: $user->getEmail(),
                address: "",
                buyer_name: $user->getFullName(),
                item_name: 'Don supplémentaire',
                item_number: "_{$user->getId()}",
                invoice: "INV" . '-' . $user->getId(),
                custom: json_encode([
                    'donation_id' => $donation->getId(),
                    'payment_type' => 'supplementary'
                ]),
                ipn_url: $this->router->generate('_webhook_controller', ['type' => self::PAYMENT_PROVIDER], UrlGeneratorInterface::ABSOLUTE_URL)
            );

            if ($result['error'] !== 'ok') {
                throw new RuntimeException($result['error']);
            }

            $result['result']['entityId'] = $donation->getId();
            return $result['result'];
        } catch (Exception $e) {
            $this->logger->error('CoinPayments transaction creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            throw $e;
        }
    }
}
