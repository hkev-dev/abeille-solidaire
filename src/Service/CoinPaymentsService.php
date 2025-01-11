<?php

namespace App\Service;

use CoinpaymentsAPI;
use Psr\Log\LoggerInterface;
use App\Exception\WebhookException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CoinPaymentsService
{
    private CoinpaymentsAPI $coinPayments;

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly LoggerInterface $logger
    ) {
        // Initialize CoinPayments API
        $this->coinPayments = new CoinpaymentsAPI(
            $this->params->get('coinpayments.private_key'),
            $this->params->get('coinpayments.public_key'),
            'json'
        );
    }

    public function getAcceptedCurrencies(): array
    {
        try {
            $result = $this->coinPayments->GetRates();

            if ($result['error'] !== 'ok') {
                throw new \Exception($result['error'] ?? 'Failed to fetch rates');
            }

            $accepted = [];
            foreach ($result['result'] as $coin => $data) {
                if (!is_array($data)) continue;
                
                $isAccepted = $data['accepted'] ?? true;

                if ($isAccepted) {
                    $accepted[$data['name'] ?? $coin] = $coin;
                }
            }

            // Add LTCT for testing environments
            if ($this->params->get('kernel.environment') !== 'prod') {
                $accepted['Litecoin Testnet'] = 'LTCT';
            }

            return $accepted;

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch accepted cryptocurrencies', [
                'error' => $e->getMessage()
            ]);

            // Return LTCT as fallback in non-prod environments
            if ($this->params->get('kernel.environment') !== 'prod') {
                return ['Litecoin Testnet' => 'LTCT'];
            }

            return [];
        }
    }

    public function createWithdrawal(float $amount, string $currency, string $address): array
    {
        try {
            $result = $this->coinPayments->CreateWithdrawal([
                'amount' => $amount,
                'currency' => $currency,
                'address' => $address,
                'auto_confirm' => 1,
                'ipn_url' => $this->params->get('app.url') . '/webhook/coinpayments/withdrawal'
            ]);

            if ($result['error'] !== 'ok') {
                throw new \Exception($result['error']);
            }

            return [
                'id' => $result['result']['id'],
                'status' => $result['result']['status'] ?? 0,
                'amount' => $amount,
                'currency' => $currency,
                'address' => $address
            ];

        } catch (\Exception $e) {
            $this->logger->error('CoinPayments withdrawal creation failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'currency' => $currency
            ]);
            throw $e;
        }
    }

    public function verifyIpn(array $ipnData, string $hmac): bool
    {
        if (!isset($ipnData['ipn_mode'], $ipnData['merchant'])) {
            throw new WebhookException('Invalid IPN data format');
        }

        if ($ipnData['ipn_mode'] !== 'hmac') {
            throw new WebhookException('Invalid IPN mode');
        }

        $merchantId = $this->params->get('coinpayments.merchant_id');
        if ($ipnData['merchant'] !== $merchantId) {
            throw new WebhookException('Invalid merchant ID');
        }

        $rawPostData = file_get_contents('php://input');
        if (empty($rawPostData)) {
            throw new WebhookException('Empty POST data');
        }

        $calculatedHmac = hash_hmac(
            'sha512', 
            $rawPostData, 
            $this->params->get('coinpayments.ipn_secret')
        );

        return hash_equals($calculatedHmac, $hmac);
    }

    public function getTransactionInfo(string $txnId): array
    {
        try {
            $result = $this->coinPayments->GetTxInfoSingle($txnId);

            if ($result['error'] !== 'ok') {
                throw new \Exception($result['error']);
            }

            return $result['result'];

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch CoinPayments transaction info', [
                'error' => $e->getMessage(),
                'txn_id' => $txnId
            ]);
            throw $e;
        }
    }
}
