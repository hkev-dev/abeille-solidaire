<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const CATEGORIES = [
        [
            'name' => 'Technology',
            'icon' => 'icon-online',
            'projectCount' => 15,
            'isActive' => true
        ],
        [
            'name' => 'Fashion',
            'icon' => 'icon-skincare',
            'projectCount' => 8,
            'isActive' => true
        ],
        [
            'name' => 'Videos',
            'icon' => 'icon-photograph',
            'projectCount' => 12,
            'isActive' => true
        ],
        [
            'name' => 'Education',
            'icon' => 'icon-translation',
            'projectCount' => 20,
            'isActive' => true
        ],
        [
            'name' => 'Design',
            'icon' => 'icon-design-thinking',
            'projectCount' => 10,
            'isActive' => true
        ],
        [
            'name' => 'Medical',
            'icon' => 'icon-patient',
            'projectCount' => 5,
            'isActive' => true
        ]
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::CATEGORIES as $categoryData) {
            $category = new Category();
            $category->setName($categoryData['name']);
            $category->setIcon($categoryData['icon']);
            $category->setProjectCount($categoryData['projectCount']);
            $category->setIsActive($categoryData['isActive']);

            $manager->persist($category);
            
            // Store reference for potential future relations
            $this->addReference('category_' . strtolower($categoryData['name']), $category);
        }

        $manager->flush();
    }
}
