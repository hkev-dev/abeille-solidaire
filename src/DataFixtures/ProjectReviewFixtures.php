<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\ProjectReview;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ProjectReviewFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $reviews = [
            [
                'project' => 'audiophile_first_smart_wireless_headphones',
                'author' => 'jane_smith',
                'comment' => 'The features look amazing! Looking forward to testing the noise cancellation.',
                'rating' => 5
            ],
            [
                'project' => 'audiophile_first_smart_wireless_headphones',
                'author' => 'john_doe',
                'comment' => 'Innovative concept, especially the AI features.',
                'rating' => 4
            ],
            [
                'project' => 'eco_friendly_fashion_collection',
                'author' => 'john_doe',
                'comment' => 'Love the sustainable approach to fashion.',
                'rating' => 5
            ]
        ];

        foreach ($reviews as $reviewData) {
            $review = new ProjectReview();
            $review->setProject($this->getReference('project_' . $reviewData['project'], Project::class))
                ->setAuthor($this->getReference('user_' . $reviewData['author'], User::class))
                ->setComment($reviewData['comment'])
                ->setRating($reviewData['rating']);

            $manager->persist($review);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
            UserFixtures::class
        ];
    }
}
