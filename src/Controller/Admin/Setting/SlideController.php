<?php

declare(strict_types=1);

namespace App\Controller\Admin\Setting;

use App\Entity\MainSlider;
use App\Form\MainSliderType;
use App\Repository\MainSliderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/setting/slide', name: 'app.admin.setting.slide')]
#[IsGranted('ROLE_ADMIN')]
class SlideController extends AbstractController
{
    #[Route('/', name: '')]
    public function slide(PaginatorInterface $paginator, Request $request, MainSliderRepository $mainSliderRepository): Response
    {
        $query = $mainSliderRepository->createQueryBuilder('e')
            ->orderBy('e.position', 'ASC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $request->query->getInt('perpage', 10),
        );

        return $this->render('admin/pages/setting/slide/index.html.twig', [
            'pagination' => $pagination
        ]);
    }
    #[Route('/new', name: '.new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $slide = new MainSlider();
        $form = $this->createForm(MainSliderType::class, $slide);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($slide);
            $entityManager->flush();

            return $this->redirectToRoute('app.admin.setting.slide');
        }

        return $this->render('admin/pages/setting/slide/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/update', name: '.update', methods: ['GET', 'POST'])]
    public function slideUpdate(MainSlider $slide, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MainSliderType::class, $slide);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre projet a été mis à jour avec succès.');
            return $this->redirectToRoute('app.admin.setting.slide');
        }


        return $this->render('admin/pages/setting/slide/update.html.twig', [
            'slide' => $slide,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: '.delete', methods: ['POST'])]
    public function delete(MainSlider $slide, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($slide);
        $entityManager->flush();

        return $this->redirectToRoute('app.admin.setting.slide');
    }
}
