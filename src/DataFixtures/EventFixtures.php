<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\EventContent;
use App\Entity\EventDetails;
use App\Entity\EventCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Faker\Factory;

class EventFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private KernelInterface $kernel,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $faker->addProvider(new \Faker\Provider\Lorem($faker));

        $eventsData = [
            [
                'title' => 'End of Year Business Workshop',
                'sourceImage' => 'events-page-img-1-1.jpg',
                'category' => 'workshop',
                'content' => [
                    'description' => $faker->paragraphs(3, true) . "\n\n" .
                        "Key Topics:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence() . "\n\n" .
                        $faker->paragraphs(2, true),
                    'requirements' => "Essential Requirements:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence() . "\n\n" .
                        "Recommended:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence()
                ],
                'details' => [
                    'startDate' => '2024-12-28',
                    'endDate' => '2024-12-29',
                    'startTime' => '9:00 AM',
                    'endTime' => '5:00 PM',
                    'phone' => '92 666 888 0000',
                    'email' => 'workshop@event.com',
                    'location' => '8 Street, San Marcos London D29, UK'
                ]
            ],
            [
                'title' => 'New Year Crowdfunding Launch Event',
                'sourceImage' => 'events-page-img-1-2.jpg',
                'category' => 'crowdfunding',
                'content' => [
                    'description' => $faker->paragraphs(2, true) . "\n\n" .
                        "Event Highlights:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence() . "\n\n" .
                        $faker->paragraphs(2, true) . "\n\n" .
                        "Who Should Attend:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence(),
                    'requirements' => "What to Bring:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence() . "\n\n" .
                        "Pre-Event Preparation:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence()
                ],
                'details' => [
                    'startDate' => '2025-01-15',
                    'endDate' => '2025-01-16',
                    'startTime' => '10:00 AM',
                    'endTime' => '4:00 PM',
                    'phone' => '92 666 888 1111',
                    'email' => 'newyear@crowdfunding.com',
                    'location' => 'Grand Hotel Conference Center, London'
                ]
            ],
            [
                'title' => 'Winter Networking Mixer',
                'sourceImage' => 'events-page-img-1-3.jpg',
                'category' => 'networking',
                'content' => [
                    'description' => $faker->paragraphs(2, true) . "\n\n" .
                        "What to Expect:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence() . "\n\n" .
                        $faker->paragraphs(1, true) . "\n\n" .
                        "Networking Opportunities:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence(),
                    'requirements' => "Required Items:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence() . "\n\n" .
                        "Suggested Preparation:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence()
                ],
                'details' => [
                    'startDate' => '2024-12-21',
                    'endDate' => null,
                    'startTime' => '6:00 PM',
                    'endTime' => '9:00 PM',
                    'phone' => '92 666 888 2222',
                    'email' => 'winter@networking.com',
                    'location' => 'The Social Impact Hub, Manchester'
                ]
            ],
            [
                'title' => '2025 Sustainability Summit',
                'sourceImage' => 'events-page-img-1-4.jpg',
                'category' => 'workshop',
                'content' => [
                    'description' => $faker->paragraphs(2, true) . "\n\n" .
                        "Summit Agenda:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence() . "\n\n" .
                        $faker->paragraphs(2, true) . "\n\n" .
                        "Expected Outcomes:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence(),
                    'requirements' => "Participant Prerequisites:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence() . "\n\n" .
                        "Materials to Bring:\n" .
                        "- " . $faker->sentence() . "\n" .
                        "- " . $faker->sentence()
                ],
                'details' => [
                    'startDate' => '2025-01-20',
                    'endDate' => '2025-01-21',
                    'startTime' => '9:00 AM',
                    'endTime' => '5:00 PM',
                    'phone' => '92 666 888 3333',
                    'email' => 'summit@green.com',
                    'location' => 'Eco Business Center, Bristol'
                ]
            ]
        ];

        foreach ($eventsData as $eventData) {
            $event = new Event();
            $event->setTitle($eventData['title']);
            $event->setCategory($this->getReference('event_category_' . $eventData['category'], EventCategory::class));

            // Handle image upload
            $sourcePath = $this->kernel->getProjectDir() . '/assets/landing/images/events/' . $eventData['sourceImage'];
            if (file_exists($sourcePath)) {
                $uploadDir = $this->kernel->getProjectDir() . '/public/uploads/events';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $tempFile = tempnam(sys_get_temp_dir(), 'event');
                copy($sourcePath, $tempFile);

                $uploadedFile = new UploadedFile(
                    $tempFile,
                    $eventData['sourceImage'],
                    'image/jpeg',
                    null,
                    true
                );

                $event->setImageFile($uploadedFile);
            }

            // Create and set content first
            $content = new EventContent();
            $content->setDescription($eventData['content']['description']);
            $content->setRequirements($eventData['content']['requirements']);
            $content->setEvent($event);
            $event->setContent($content);

            // Create and set details
            $details = new EventDetails();
            $details->setStartDate(new \DateTime($eventData['details']['startDate']));
            $details->setEndDate($eventData['details']['endDate'] ? new \DateTime($eventData['details']['endDate']) : null);
            $details->setStartTime($eventData['details']['startTime']);
            $details->setEndTime($eventData['details']['endTime']);
            $details->setPhone($eventData['details']['phone']);
            $details->setEmail($eventData['details']['email']);
            $details->setLocation($eventData['details']['location']);
            $details->setEvent($event);
            $event->setDetails($details);

            $manager->persist($event);
            $manager->persist($content);
            $manager->persist($details);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            EventCategoryFixtures::class,
        ];
    }
}
