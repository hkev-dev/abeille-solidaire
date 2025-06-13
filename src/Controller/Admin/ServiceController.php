<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Cause;
use App\Entity\Service;
use App\Form\CauseType;
use App\Form\ServiceType;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/service', name: 'app.admin.service.')]
#[IsGranted('ROLE_ADMIN')]
class ServiceController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, ServiceRepository $serviceRepository, PaginatorInterface $paginator): Response
    {
        $query = $serviceRepository->createQueryBuilder('s')
            ->orderBy('s.createdAt', 'DESC');

        if ($request->query->has('q')) {
            $query->andWhere('LOWER(cause.title) LIKE LOWER(:search)')
                ->setParameter('search', '%' . $request->query->get('q') . '%');
        }

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $request->query->getInt('perpage', 10),
        );

        return $this->render('admin/pages/service/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $entity = new Service();
        $form = $this->createForm(ServiceType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();

            $this->addFlash('success', 'Le service a été ajouter avec succès.');
            return $this->redirectToRoute('app.admin.service.index');
        }

        return $this->render('admin/pages/service/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/detail/{id}', name: 'detail')]
    public function detail(Service $service): Response
    {
        return $this->render('admin/pages/service/detail.html.twig', [
            'service' => $service
        ]);
    }

    #[Route('/update/{id}', name: 'update')]
    public function update(Service $service, Request $request, EntityManagerInterface $entityManager): Response
    {

        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();
            $this->addFlash('success', 'Le service été mis à jour avec succès.');
            return $this->redirectToRoute('app.admin.service.index');
        }

        return $this->render('admin/pages/service/update.html.twig', [
            'form' => $form->createView(),
            'service' => $service
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete(Service $service, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($service);
        $entityManager->flush();

        return $this->redirectToRoute('app.admin.service.index');
    }
}
