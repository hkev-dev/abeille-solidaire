<?php

namespace App\DataFixtures;

use App\Entity\FAQ;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FAQFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faqs = [
            [
                'question' => 'How to create a campaign?',
                'answer' => 'To create a campaign, navigate to your dashboard and click on "Create Campaign". Fill in all required information including your campaign description, funding goal, and timeline.',
                'position' => 1,
                'active' => true
            ],
            [
                'question' => 'What payment methods are accepted?',
                'answer' => 'We accept various payment methods including credit/debit cards, PayPal, and bank transfers. All payments are processed securely through our platform.',
                'position' => 2,
                'active' => true
            ],
            [
                'question' => 'How long can my campaign run?',
                'answer' => 'Campaign duration can be set between 1 to 60 days. Choose a timeline that makes sense for your funding goals and project timeline.',
                'position' => 3,
                'active' => true
            ],
            [
                'question' => 'What happens if I don\'t reach my funding goal?',
                'answer' => 'If you don\'t reach your funding goal, all pledges will be returned to the backers. This ensures transparency and trust in our platform.',
                'position' => 4,
                'active' => true
            ],
            [
                'question' => 'Can I edit my campaign after launching?',
                'answer' => 'Yes, you can edit certain aspects of your campaign after launch, such as the description and updates. However, core elements like funding goals cannot be changed.',
                'position' => 5,
                'active' => true
            ],
            [
                'question' => 'What fees does the platform charge?',
                'answer' => 'Our platform charges a 5% fee on successfully funded campaigns, plus payment processing fees which vary by payment method.',
                'position' => 6,
                'active' => true
            ],
            [
                'question' => 'How do I receive my funds?',
                'answer' => 'Once your campaign successfully ends, funds will be transferred to your verified bank account within 14 business days.',
                'position' => 7,
                'active' => true
            ],
            [
                'question' => 'Can international creators use the platform?',
                'answer' => 'Yes, we welcome creators from many countries. However, certain restrictions may apply based on your location.',
                'position' => 8,
                'active' => true
            ]
        ];

        foreach ($faqs as $faqData) {
            $faq = new FAQ();
            $faq->setQuestion($faqData['question'])
                ->setAnswer($faqData['answer'])
                ->setPosition($faqData['position'])
                ->setIsActive($faqData['active']);

            $manager->persist($faq);
        }

        $manager->flush();
    }
}
