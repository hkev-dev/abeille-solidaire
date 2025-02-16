<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Entity\Withdrawal;
use App\Form\WithdrawalFormType;
use App\Repository\WithdrawalRepository;
use App\Service\Payment\CoinPaymentsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
                'weeklyLimit' => Withdrawal::MAX_AMOUNT,
                'minWithdrawal' => Withdrawal::MIN_AMOUNT,
            ],
            'cryptoCurrencies' => $this->coinPaymentsService->getAcceptedCryptoCurrencies()
        ];

        return $this->render('user/pages/wallet/index.html.twig', $data);
    }

    #[Route('/withdraw', name: 'withdraw')]
    public function withdraw(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Check all withdrawal prerequisites
        $canWithdraw = $user->isEligibleForWithdrawal();

        $withdrawal = new Withdrawal();
        $form = $this->createForm(WithdrawalFormType::class, $withdrawal, [
            'payment_methods' => $user->getPaymentMethods()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //TODO on witdrawal submited
            // Handle withdrawal submission
            // This would be implemented in a service
            $withdrawal->setUser($user);
            $entityManager->persist($withdrawal);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande de retrait a été envoyer.');

            return $this->redirectToRoute('app.user.wallet.index');
        }

        return $this->render('user/pages/wallet/withdraw.html.twig', [
            'form' => $form->createView(),
            'canWithdraw' => $canWithdraw
        ]);
    }

    #[Route('/history', name: 'history')]
    public function history(): Response
    {
        $user = $this->getUser();
        $withdrawals = $this->withdrawalRepository->findUserWithdrawals($user);

        // Calculate simple stats
        $totalAmount = array_reduce($withdrawals, fn($carry, $withdrawal) => $carry + $withdrawal->getAmount(), 0.0);
        $totalFees = array_reduce($withdrawals, fn($carry, $withdrawal) => $carry + $withdrawal->getFeeAmount(), 0.0);
        $stripeCount = count(array_filter($withdrawals, fn($w) => $w->getWithdrawalMethod() === 'stripe'));
        $cryptoCount = count(array_filter($withdrawals, fn($w) => $w->getWithdrawalMethod() === 'crypto'));
        $successCount = count(array_filter($withdrawals, fn($w) => $w->getStatus() === 'processed'));

        return $this->render('user/pages/wallet/history.html.twig', [
            'withdrawals' => $withdrawals,
            'total_amount' => $totalAmount,
            'total_fees' => $totalFees,
            'stats' => [
                'stripe_count' => $stripeCount,
                'crypto_count' => $cryptoCount,
                'success_rate' => count($withdrawals) > 0 ? ($successCount / count($withdrawals)) * 100 : 0
            ]
        ]);
    }
}
