<?php

namespace App\DataFixtures;

use App\Entity\ProjectCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProjectCategoryFixtures extends Fixture
{
    private array $categories = [
        'technology' => ['name' => 'Technology', 'icon' => 'fas fa-microchip'],
        'fashion' => ['name' => 'Fashion', 'icon' => 'fas fa-tshirt'],
        'design' => ['name' => 'Design', 'icon' => 'fas fa-pencil-ruler'],
        'food' => ['name' => 'Food', 'icon' => 'fas fa-utensils'],
        'art' => ['name' => 'Art', 'icon' => 'fas fa-palette'],
        'games' => ['name' => 'Games', 'icon' => 'fas fa-gamepad']
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
