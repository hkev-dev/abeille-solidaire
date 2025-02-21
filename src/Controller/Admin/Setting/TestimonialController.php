<?php

declare(strict_types=1);

namespace App\Controller\Admin\Setting;

use App\Entity\FAQ;
use App\Entity\MainSlider;
use App\Entity\Testimonial;
use App\Form\FaqType;
use App\Form\MainSliderType;
use App\Form\TestimonialType;
use App\Repository\FAQRepository;
use App\Repository\MainSliderRepository;
use App\Repository\TestimonialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/setting/testimonial', name: 'app.admin.setting.testimonial.')]
#[IsGranted('ROLE_ADMIN')]
class TestimonialController extends AbstractController
{
    #[Route('/', name: 'list')]
    public function list(PaginatorInterface $paginator, Request $request, TestimonialRepository $repository): Response
    {
        $query = $repository->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $request->query->getInt('perpage', 10),
        );

        return $this->render('admin/pages/setting/testimonial/index.html.twig', [
            'pagination' => $pagination
        ]);
    }
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $entity = new Testimonial();
        $form = $this->createForm(TestimonialType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();

            $this->addFlash('success', 'L\'avis a été ajouter avec succès.');
            return $this->redirectToRoute('app.admin.setting.testimonial.list');
        }

        return $this->render('admin/pages/setting/testimonial/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/update', name: 'update', methods: ['GET', 'POST'])]
    public function update(Testimonial $entity, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TestimonialType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'L\'avis a été mis à jour avec succès.');
            return $this->redirectToRoute('app.admin.setting.testimonial.list');
        }


        return $this->render('admin/pages/setting/testimonial/update.html.twig', [
            'testimonial' => $entity,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Testimonial $entity, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($entity);
        $entityManager->flush();

        $this->addFlash('success', 'L\'avis a été supprimé avec succès.');
        return $this->redirectToRoute('app.admin.setting.testimonial.list');
    }
}
