<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\MainSlider;
use App\Entity\Project;
use App\Form\MainSliderType;
use App\Form\ProjectType;
use App\Repository\MainSliderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/setting', name: 'app.admin.setting.')]
#[IsGranted('ROLE_ADMIN')]
class SettingController extends AbstractController
{
    #[Route('/general', name: 'general')]
    public function general(): Response
    {
        return $this->render('admin/pages/setting/general.html.twig');
    }
    #[Route('/slide', name: 'slide')]
    public function slide(PaginatorInterface $paginator, Request $request, MainSliderRepository $mainSliderRepository): Response
    {
        $query = $mainSliderRepository->createQueryBuilder('e')
            ->orderBy('e.updatedAt', 'DESC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $request->query->getInt('perpage', 10),
        );

        return $this->render('admin/pages/setting/slide/index.html.twig', [
            'pagination' => $pagination
        ]);
    }
    #[Route('/slide/new', name: 'slide.new', methods: ['GET', 'POST'])]
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

    #[Route('/slide/{id}/update', name: 'slide.update', methods: ['GET', 'POST'])]
    public function slideUpdate(MainSlider $slide, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MainSliderType::class, $slide);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre projet a été mis à jour avec succès.');
            return $this->redirectToRoute('app.admin.project.index');
        }


        return $this->render('admin/pages/setting/slide/update.html.twig', [
            'slide' => $slide,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/slide/{id}/delete', name: 'slide.delete', methods: ['POST'])]
    public function delete(MainSlider $slide, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($slide);
        $entityManager->flush();

        return $this->redirectToRoute('app.admin.setting.slide');
    }
}
