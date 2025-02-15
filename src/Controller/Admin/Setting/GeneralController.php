<?php

declare(strict_types=1);

namespace App\Controller\Admin\Setting;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/setting/general', name: 'app.admin.setting.general')]
#[IsGranted('ROLE_ADMIN')]
class GeneralController extends AbstractController
{
    #[Route('', name: '')]
    public function general(): Response
    {
        return $this->render('admin/pages/setting/general.html.twig');
    }
}
