<?php

namespace App\Controller\Admin;

use App\Entity\MainSlider;
use App\Form\MainSliderType;
use App\Repository\MainSliderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/slider')]
class MainSliderController extends AbstractController
{
    #[Route('/', name: 'admin.slider.index', methods: ['GET'])]
    public function index(MainSliderRepository $mainSliderRepository): Response
    {
        return $this->render('admin/slider/index.html.twig', [
            'slides' => $mainSliderRepository->findBy([], ['position' => 'ASC'])
        ]);
    }

    #[Route('/new', name: 'admin.slider.new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $slide = new MainSlider();
        $form = $this->createForm(MainSliderType::class, $slide);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($slide);
            $entityManager->flush();

            return $this->redirectToRoute('admin.slider.index');
        }

        return $this->render('admin/slider/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/edit', name: 'admin.slider.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MainSlider $slide, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MainSliderType::class, $slide);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('admin.slider.index');
        }

        return $this->render('admin/slider/edit.html.twig', [
            'slide' => $slide,
            'form' => $form
        ]);
    }
}
