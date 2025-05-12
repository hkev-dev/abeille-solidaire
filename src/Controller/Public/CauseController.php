<?php

namespace App\Controller\Public;

use App\Entity\Cause;
use App\Repository\CauseRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\PDonationPaymentSelectionType;

#[Route('/cause', name: 'landing.cause.')]
class CauseController extends AbstractController
{
    public function __construct(
        private readonly CauseRepository $causeRepository,
        private readonly PaginatorInterface $paginator
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $query = $this->causeRepository->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ;

        /*if ($request->query->has('category')) {
            $query->andWhere('p.category = :category')
                ->setParameter('category', $request->query->get('category'));
        }*/

        if ($request->query->has('q')) {
            $query->andWhere('LOWER(p.title) LIKE LOWER(:search)')
                ->setParameter('search', '%' . $request->query->get('q') . '%');
        }

        $causes = $this->paginator->paginate(
            $query->getQuery(),
            $request->query->getInt('page', 1),
            9 // Number of items per page
        );

        return $this->render('public/pages/causes/index.html.twig', [
            'causes' => $causes
        ]);
    }

    #[Route('/{slug}', name: 'details')]
    public function details(string $slug): Response
    {
        /** @var Cause $cause */
        $cause = $this->causeRepository->findOneBySlug($slug);

        if (!$cause) {
            throw $this->createNotFoundException('Cause not found');
        }

        $form = $this->createForm(PDonationPaymentSelectionType::class);

        return $this->render('public/pages/causes/details.html.twig', [
            'cause' => $cause,
            'form' => $form,
            'stripe_public_key' => $this->getParameter('stripe.public_key'),
        ]);
    }

    #[Route('/support/{slug}', name: 'support')]
    public function support(string $slug): Response
    {
        $form = $this->createForm(PDonationPaymentSelectionType::class);
        return $this->render('public/pages/causes/ponctual-donationpayment.html.twig', [
            'form' => $form,
            'slug' => $slug,
            'stripe_public_key' => $this->getParameter('stripe.public_key'),
        ]);
    }
}
