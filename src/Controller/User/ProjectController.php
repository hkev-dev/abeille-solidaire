<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Entity\Project;
use App\Form\ProjectType;
use App\Entity\ProjectFAQ;
use App\Entity\ProjectUpdate;
use App\Form\ProjectUpdateType;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/user/project', name: 'app.user.project.')]
#[IsGranted('ROLE_USER')]
class ProjectController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectRepository $projectRepository
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $project = $user->getProject();

        if (!$project) {
            return $this->redirectToRoute('app.user.project.create');
        }

        return $this->render('user/pages/project/index.html.twig', [
            'project' => $project
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Check if user has already created a project
        if ($user->hasProject()) {
            $this->addFlash('error', 'Vous avez déjà un projet actif.');
            return $this->redirectToRoute('app.user.project.index');
        }

        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $project->setCreator($user);
                $this->entityManager->persist($project);
                $this->entityManager->flush();

                $this->addFlash('success', 'Votre projet a été créé avec succès.');
                return $this->redirectToRoute('app.user.project.index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création du projet.');
                return $this->redirectToRoute('app.user.project.create');
            }
        }

        return $this->render('user/pages/project/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/update', name: 'update', methods: ['GET', 'POST'])]
    public function update(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $project = $this->projectRepository->findOneBy(['creator' => $user]);

        if (!$project) {
            $this->addFlash('error', 'Vous n\'avez pas encore de projet.');
            return $this->redirectToRoute('app.user.project.create');
        }

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Votre projet a été mis à jour avec succès.');
            return $this->redirectToRoute('app.user.project.index');
        }

        return $this->render('user/pages/project/update.html.twig', [
            'form' => $form->createView(),
            'project' => $project
        ]);
    }

    #[Route('/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $project = $user->getProject();

        if (!$project) {
            $this->addFlash('error', 'Projet introuvable.');
            return $this->redirectToRoute('app.user.project.index');
        }

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $project->getId(), $token)) {
            try {
                $this->entityManager->remove($project);
                $this->entityManager->flush();
                $this->addFlash('success', 'Projet supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du projet.');
            }
        }

        return $this->redirectToRoute('app.user.project.index');
    }

    #[Route('/updates', name: 'updates', methods: ['GET'])]
    public function updates(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $project = $user->getProject();

        if (!$project) {
            $this->addFlash('error', 'Vous n\'avez pas encore de projet.');
            return $this->redirectToRoute('app.user.project.create');
        }

        return $this->render('user/pages/project/updates.html.twig', [
            'project' => $project
        ]);
    }

    #[Route('/faqs', name: 'faqs', methods: ['GET'])]
    public function faqs(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $project = $user->getProject();

        if (!$project) {
            $this->addFlash('error', 'Vous n\'avez pas encore de projet.');
            return $this->redirectToRoute('app.user.project.create');
        }

        return $this->render('user/pages/project/faqs.html.twig', [
            'project' => $project
        ]);
    }

    #[Route('/updates/create', name: 'updates.create', methods: ['GET', 'POST'])]
    public function createUpdate(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $project = $user->getProject();

        if (!$project) {
            $this->addFlash('error', 'Vous n\'avez pas encore de projet.');
            return $this->redirectToRoute('app.user.project.create');
        }

        $update = new ProjectUpdate();
        $form = $this->createForm(ProjectUpdateType::class, $update);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $update->setProject($project);
                $this->entityManager->persist($update);
                $this->entityManager->flush();

                $this->addFlash('success', 'Mise à jour publiée avec succès.');
                return $this->redirectToRoute('app.user.project.updates');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la publication.');
            }
        }

        return $this->render('user/pages/project/updates-create.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
            'update' => $update,
        ]);
    }

    #[Route('/updates/{id}', name: 'updates.delete', methods: ['DELETE', 'POST'])]
    public function deleteUpdate(ProjectUpdate $update): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($update->getProject()->getCreator() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette mise à jour.');
        }

        try {
            $this->entityManager->remove($update);
            $this->entityManager->flush();
            $this->addFlash('success', 'Mise à jour supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la mise à jour.');
        }

        return $this->redirectToRoute('app.user.project.updates');
    }

    #[Route('/updates/{id}/edit', name: 'updates.edit', methods: ['GET', 'POST'])]
    public function editUpdate(Request $request, ProjectUpdate $update): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($update->getProject()->getCreator() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette mise à jour.');
        }

        $form = $this->createForm(ProjectUpdateType::class, $update);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->flush();
                $this->addFlash('success', 'Mise à jour modifiée avec succès.');
                return $this->redirectToRoute('app.user.project.updates');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification.');
            }
        }

        return $this->render('user/pages/project/updates-create.html.twig', [
            'project' => $update->getProject(),
            'form' => $form->createView(),
            'update' => $update,
            'isEdit' => true
        ]);
    }
}