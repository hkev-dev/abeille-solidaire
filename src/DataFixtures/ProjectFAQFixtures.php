<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\ProjectFAQ;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProjectFAQFixtures extends Fixture implements DependentFixtureInterface
{
    private array $commonQuestions = [
        'What is your return policy?' => 'We offer a 30-day money-back guarantee on all our products.',
        'Do you ship internationally?' => 'Yes, we ship to most countries worldwide.',
        'How long is the warranty?' => 'All our products come with a 2-year limited warranty.',
        'What payment methods do you accept?' => 'We accept all major credit cards, PayPal, and bank transfers.',
        'Is there a pre-order discount?' => 'Yes, early backers get a 20% discount on the retail price.'
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // For each project (0-19 as per ProjectFixtures), create 3-5 FAQs
        for ($i = 0; $i < 20; $i++) {
            $project = $this->getReference('project_' . $i, Project::class);
            $numFaqs = $faker->numberBetween(3, 5);

            // Add some common questions
            $questions = $this->commonQuestions;

            // Add project-specific questions
            for ($j = 0; $j < $numFaqs; $j++) {
                $faq = new ProjectFAQ();

                if (!empty($questions)) {
                    // Use a common question 70% of the time
                    if ($faker->boolean(70)) {
                        $question = array_key_first($questions);
                        $answer = $questions[$question];
                        unset($questions[$question]);
                    } else {
                        $question = $faker->sentence(rand(4, 8)) . '?';
                        $answer = $faker->paragraph(rand(2, 4));
                    }
                } else {
                    $question = $faker->sentence(rand(4, 8)) . '?';
                    $answer = $faker->paragraph(rand(2, 4));
                }

                $faq->setProject($project)
                    ->setQuestion($question)
                    ->setAnswer($answer);

                $manager->persist($faq);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ProjectFixtures::class];
    }
}
