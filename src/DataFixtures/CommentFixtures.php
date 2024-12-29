<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\NewsArticle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CommentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        foreach (range(0, 4) as $articleIndex) {
            $commentsCount = $faker->numberBetween(3, 8);

            for ($i = 0; $i < $commentsCount; $i++) {
                $comment = new Comment();
                $comment->setArticle($this->getReference('article_' . $articleIndex, NewsArticle::class))
                    ->setAuthor($faker->name)
                    ->setEmail($faker->email)
                    ->setContent($this->generateComment($faker));

                $manager->persist($comment);
            }
        }

        $manager->flush();
    }

    private function generateComment(\Faker\Generator $faker): string
    {
        $type = $faker->randomElement(['positive', 'question', 'suggestion']);

        return match ($type) {
            'positive' => $faker->randomElement([
                'Great article! ' . $faker->sentence(2),
                'This is very insightful. ' . $faker->sentence(2),
                'Thanks for sharing these valuable insights. ' . $faker->sentence(2)
            ]),
            'question' => $faker->randomElement([
                'Interesting point about ' . $faker->words(3, true) . '. Have you considered ' . $faker->sentence(2),
                'What are your thoughts on ' . $faker->words(4, true) . '?',
                'Could you elaborate more on ' . $faker->words(3, true) . '?'
            ]),
            'suggestion' => $faker->randomElement([
                'You might also want to consider ' . $faker->sentence(2),
                'Another perspective could be ' . $faker->sentence(2),
                'It would be great to see ' . $faker->sentence(2)
            ])
        };
    }

    public function getDependencies(): array
    {
        return [
            NewsArticleFixtures::class,
        ];
    }
}
