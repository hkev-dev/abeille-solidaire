<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\MainSlider;
use App\Repository\MainSliderRepository;
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
}
