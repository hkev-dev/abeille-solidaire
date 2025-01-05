<?php

namespace App\DataFixtures;

use App\Entity\ProjectCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProjectCategoryFixtures extends Fixture
{
    private array $categories = [
        'aide-financiere' => ['name' => 'Aide Financière', 'icon' => 'fas fa-hand-holding-usd'],
        'association' => ['name' => 'Association', 'icon' => 'fas fa-users'],
        'bien-etre' => ['name' => 'Bien-être', 'icon' => 'fas fa-spa'],
        'commerce' => ['name' => 'Commerce', 'icon' => 'fas fa-store'],
        'digital' => ['name' => 'Digital & Numérique', 'icon' => 'fas fa-laptop-code'],
        'etudes' => ['name' => 'Études & Formation', 'icon' => 'fas fa-graduation-cap'],
        'equipement' => ['name' => 'Équipement', 'icon' => 'fas fa-tools'],
        'formation' => ['name' => 'Formation Professionnelle', 'icon' => 'fas fa-chalkboard-teacher'],
        'immobilier' => ['name' => 'Immobilier', 'icon' => 'fas fa-home'],
        'permis' => ['name' => 'Permis de Conduire', 'icon' => 'fas fa-id-card'],
        'sante' => ['name' => 'Santé & Médical', 'icon' => 'fas fa-heartbeat'],
        'travaux' => ['name' => 'Travaux & Rénovation', 'icon' => 'fas fa-hard-hat'],
        'voyages' => ['name' => 'Voyages', 'icon' => 'fas fa-plane-departure'],
        'vehicules' => ['name' => 'Véhicules', 'icon' => 'fas fa-car'],
        'autres' => ['name' => 'Autres Projets', 'icon' => 'fas fa-plus-circle']
    ];

    public function load(ObjectManager $manager): void
    {
        foreach ($this->categories as $slug => $data) {
            $category = new ProjectCategory();
            $category->setName($data['name'])
                ->setIcon($data['icon'])
                ->setIsActive(true)
                ->setProjectCount(0);

            $manager->persist($category);
            $this->addReference('project_category_' . $slug, $category);
        }

        $manager->flush();
    }
}
