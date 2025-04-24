<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\KycService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ExcelExporterService;

#[Route('/admin/user', name: 'app.admin.user.')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(UserRepository $userRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $userRepository->createQueryBuilder('user')
            ->orderBy('user.updatedAt', 'DESC');

        // Ajout du filtre de recherche
        $search = $request->query->get('q');
        if (!empty($search)) {
            $fields = ['username', 'email', 'lastName', 'firstName'];
            $conditions = [];

            foreach ($fields as $field) {
                $conditions[] = "LOWER(user.$field) LIKE LOWER(:search)";
            }

            $query
                ->andWhere(implode(' OR ', $conditions))
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $request->query->getInt('perpage', 10),
        );

        return $this->render('admin/pages/user/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/{id}/kyc-verification', name: 'kyc_verification')]
    public function kycValidation(User $user, Request $request, KycService $kycService)
    {
        $kycVerification = $user->getKycVerificationWaitingValidation();

        if (!$kycVerification) {
            $this->addFlash('warning', 'Aucune vérification KYC en attente pour cet utilisateur');

            return $this->redirectToRoute('app.admin.user.index');
        }

        if ($request->isMethod("POST")) {
            $comment = $request->request->get('comment');
            $action = $request->request->get('action');

            if (!in_array($action, ['approve', 'reject'])) {
                $this->addFlash('danger', 'Action invalide');
            } else {
                if ($action === "approve") {
                    $kycService->approveVerification($kycVerification->getReferenceId(), $comment);
                } else {
                    $kycService->rejectVerification($kycVerification->getReferenceId(), $comment);
                }

                $this->addFlash('success', 'Vérification KYC traitée avec succès');

                return $this->redirectToRoute('app.admin.user.index');
            }
        }

        return $this->render('admin/pages/user/kyc-verification.html.twig', [
            'kycVerification' => $kycVerification,
            'submittedData' => $kycVerification->getSubmittedData(),
            'documents' => $kycVerification->getDocuments(),
        ]);

    }

    #[Route('/export', name: 'export')]
    public function export(Request $request, UserRepository $userRepository, ExcelExporterService $excelExporter): Response
    {
        $data = $userRepository->getAll();

        $formattedData = [];
        foreach ($data as $entity) {
            $formattedData[] = [
                $entity->getId(),
                $entity->getName(),
                $entity->getEmail(),
                $entity->getPhone()
            ];
        }

        $headers = ['ID', 'Nom d\'utilisateur', 'Email', 'Phone'];
        $filename = 'export.xlsx';

        $excelExporter->export($formattedData, $headers, $filename);

        return $this->file($filename);
    }
}
