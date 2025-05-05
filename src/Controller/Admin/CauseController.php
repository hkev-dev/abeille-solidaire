<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Cause;
use App\Form\CauseType;
use App\Repository\CauseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/cause', name: 'app.admin.cause.')]
#[IsGranted('ROLE_ADMIN')]
class CauseController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, CauseRepository $causeRepository, PaginatorInterface $paginator): Response
    {
        $query = $causeRepository->createQueryBuilder('cause')
            ->orderBy('cause.createdAt', 'DESC');

        if ($request->query->has('q')) {
            $query->andWhere('LOWER(cause.title) LIKE LOWER(:search)')
                ->setParameter('search', '%' . $request->query->get('q') . '%');
        }

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $request->query->getInt('perpage', 10),
        );

        return $this->render('admin/pages/cause/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $entity = new Cause();
        $form = $this->createForm(CauseType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();

            $this->addFlash('success', 'La cause a été ajouter avec succès.');
            return $this->redirectToRoute('app.admin.cause.index');
        }

        return $this->render('admin/pages/cause/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/detail/{id}', name: 'detail')]
    public function detail(Cause $cause): Response
    {
        return $this->render('admin/pages/cause/detail.html.twig', [
            'cause' => $cause
        ]);
    }

    #[Route('/update/{id}', name: 'update')]
    public function update(Cause $cause, Request $request, EntityManagerInterface $entityManager): Response
    {

        $form = $this->createForm(CauseType::class, $cause);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();
            $this->addFlash('success', 'La cause a été mis à jour avec succès.');
            return $this->redirectToRoute('app.admin.cause.index');
        }

        return $this->render('admin/pages/cause/update.html.twig', [
            'form' => $form->createView(),
            'cause' => $cause
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete(Cause $cause, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($cause);
        $entityManager->flush();

        return $this->redirectToRoute('app.admin.cause.index');
    }
}
