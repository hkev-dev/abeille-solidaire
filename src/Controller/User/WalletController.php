<?php

namespace App\Controller\User;

use App\Repository\WithdrawalRepository;
use App\Service\CoinPaymentsService;
use App\Form\WithdrawalFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/wallet', name: 'app.user.wallet.')]
class WalletController extends AbstractController
{
    public function __construct(
        private readonly WithdrawalRepository $withdrawalRepository,
        private readonly CoinPaymentsService $coinPaymentsService
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $user = $this->getUser();
        $recentWithdrawals = $this->withdrawalRepository->findBy(
            ['user' => $user],
            ['requestedAt' => 'DESC'],
            5
        );

        $data = [
            'walletBalance' => $user->getWalletBalance(),
            'recentWithdrawals' => $recentWithdrawals,
            'lastWithdrawal' => !empty($recentWithdrawals) ? $recentWithdrawals[0] : null,
            'pendingWithdrawals' => $this->withdrawalRepository->findBy(
                ['user' => $user, 'status' => 'pending'],
                ['requestedAt' => 'DESC']
            ),
            'withdrawalStats' => [
                'totalWithdrawn' => $this->withdrawalRepository->getTotalWithdrawnInPeriod(
                    $user,
                    new \DateTime('-30 days'),
                    new \DateTime()
                ),
                'weeklyLimit' => \App\Entity\Withdrawal::MAX_AMOUNT,
                'minWithdrawal' => \App\Entity\Withdrawal::MIN_AMOUNT,
            ],
            'cryptoCurrencies' => $this->coinPaymentsService->getAcceptedCurrencies()
        ];

        return $this->render('user/pages/wallet/index.html.twig', $data);
    }

    #[Route('/withdraw', name: 'withdraw')]
    public function withdraw(Request $request): Response
    {
        $user = $this->getUser();
        
        // Check all withdrawal prerequisites
        $canWithdraw = 
            $user->isKycVerified() && 
            $user->getProjectDescription() && 
            $user->getCurrentMembership() && 
            $user->getWalletBalance() >= \App\Entity\Withdrawal::MIN_AMOUNT;

        $form = $this->createForm(WithdrawalFormType::class, null, [
            'crypto_currencies' => $this->coinPaymentsService->getAcceptedCurrencies()
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle withdrawal submission
            // This would be implemented in a service
        }

        return $this->render('user/pages/wallet/withdraw.html.twig', [
            'form' => $form->createView(),
            'canWithdraw' => $canWithdraw
        ]);
    }

    #[Route('/history', name: 'history')]
    public function history(Request $request): Response
    {
        $user = $this->getUser();
        
        // Get filters from request
        $filters = [
            'start_date' => $request->query->get('start_date', '-30 days'),
            'end_date' => $request->query->get('end_date', 'now'),
            'status' => $request->query->get('status'),
            'method' => $request->query->get('method'),
            'search' => $request->query->get('search'), // Add search parameter
            'page' => (int)$request->query->get('page', 1),
            'total_pages' => 1 // Will be calculated based on results
        ];
        
        // Convert dates to DateTime objects
        $startDate = new \DateTime($filters['start_date']);
        $endDate = new \DateTime($filters['end_date']);
        
        // Get all withdrawals with filters
        $withdrawals = $this->withdrawalRepository->findUserWithdrawalsWithFilters(
            user: $user,
            startDate: $startDate,
            endDate: $endDate,
            status: $filters['status'],
            method: $filters['method']
        );

        // Calculate totals
        $totalAmount = array_reduce($withdrawals, fn($carry, $withdrawal) => $carry + $withdrawal->getAmount(), 0.0);
        $totalFees = array_reduce($withdrawals, fn($carry, $withdrawal) => $carry + $withdrawal->getFeeAmount(), 0.0);

        return $this->render('user/pages/wallet/history.html.twig', [
            'withdrawals' => $withdrawals,
            'total_amount' => $totalAmount,
            'total_fees' => $totalFees,
            'filters' => $filters,
            'stats' => [
                'stripe_count' => count(array_filter($withdrawals, fn($w) => $w->getWithdrawalMethod() === 'stripe')),
                'crypto_count' => count(array_filter($withdrawals, fn($w) => $w->getWithdrawalMethod() === 'crypto')),
                'success_rate' => count($withdrawals) > 0 
                    ? (count(array_filter($withdrawals, fn($w) => $w->getStatus() === 'processed')) / count($withdrawals)) * 100 
                    : 0
            ]
        ]);
    }
}
