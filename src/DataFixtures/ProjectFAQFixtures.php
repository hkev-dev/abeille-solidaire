<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\ProjectFAQ;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProjectFAQFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faqs = [
            [
                'project' => 'audiophile_first_smart_wireless_headphones',
                'question' => 'What is the battery life?',
                'answer' => 'The battery lasts up to 20 hours on a single charge.'
            ],
            [
                'project' => 'audiophile_first_smart_wireless_headphones',
                'question' => 'Is there a warranty?',
                'answer' => 'Yes, we offer a 2-year warranty on all our products.'
            ]
        ];

        foreach ($faqs as $faqData) {
            $faq = new ProjectFAQ();
            $faq->setProject($this->getReference('project_' . $faqData['project'], Project::class))
                ->setQuestion($faqData['question'])
                ->setAnswer($faqData['answer']);

            $manager->persist($faq);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ProjectFixtures::class];
    }
}
