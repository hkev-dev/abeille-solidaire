<?php

declare(strict_types=1);

namespace App\Controller\Admin\Setting;

use App\Entity\FAQ;
use App\Entity\MainSlider;
use App\Form\FaqType;
use App\Form\MainSliderType;
use App\Repository\FAQRepository;
use App\Repository\MainSliderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/setting/faq', name: 'app.admin.setting.faq')]
#[IsGranted('ROLE_ADMIN')]
class FaqController extends AbstractController
{
    #[Route('/', name: '')]
    public function list(PaginatorInterface $paginator, Request $request, FAQRepository $FAQRepository): Response
    {
        $query = $FAQRepository->createQueryBuilder('e')
            ->orderBy('e.position', 'ASC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $request->query->getInt('perpage', 10),
        );

        return $this->render('admin/pages/setting/faq/index.html.twig', [
            'pagination' => $pagination
        ]);
    }
    #[Route('/new', name: '.new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $entity = new FAQ();
        $form = $this->createForm(FaqType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();

            $this->addFlash('success', 'La question a été ajouter avec succès.');
            return $this->redirectToRoute('app.admin.setting.faq');
        }

        return $this->render('admin/pages/setting/faq/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/update', name: '.update', methods: ['GET', 'POST'])]
    public function update(FAQ $entity, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FaqType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La question a été mis à jour avec succès.');
            return $this->redirectToRoute('app.admin.setting.faq');
        }


        return $this->render('admin/pages/setting/faq/update.html.twig', [
            'faq' => $entity,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: '.delete', methods: ['POST'])]
    public function delete(FAQ $entity, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($entity);
        $entityManager->flush();

        return $this->redirectToRoute('app.admin.setting.faq');
    }
}
