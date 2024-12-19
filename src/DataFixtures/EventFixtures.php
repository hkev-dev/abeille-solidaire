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

    private function createTimeFromString(string $timeString): \DateTime
    {
        return \DateTime::createFromFormat('g:i A', $timeString);
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $faker->addProvider(new \Faker\Provider\Lorem($faker));

        // Generate 20 events (original 4 + 16 more)
        for ($i = 0; $i < 20; $i++) {
            $event = new Event();
            $event->setTitle($faker->sentence(4));
            $event->setCategory($this->getReference('event_category_' . $faker->randomElement(['workshop', 'crowdfunding', 'networking']), EventCategory::class));

            // Handle image upload - cycle through existing images
            $imageNumber = ($i % 4) + 1; // This will cycle through 1-4
            $sourceImage = "events-page-img-1-{$imageNumber}.jpg";
            
            // Handle image upload
            $sourcePath = $this->kernel->getProjectDir() . '/assets/landing/images/events/' . $sourceImage;
            if (file_exists($sourcePath)) {
                $uploadDir = $this->kernel->getProjectDir() . '/public/uploads/events';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $tempFile = tempnam(sys_get_temp_dir(), 'event');
                copy($sourcePath, $tempFile);

                $uploadedFile = new UploadedFile(
                    $tempFile,
                    $sourceImage,
                    'image/jpeg',
                    null,
                    true
                );

                $event->setImageFile($uploadedFile);
            }

            // Create and set content
            $content = new EventContent();
            $content->setDescription($faker->paragraphs(3, true));
            $content->setRequirements($faker->paragraphs(2, true));
            $content->setEvent($event);
            $event->setContent($content);

            // Create and set details
            $details = new EventDetails();
            $details->setStartDate($faker->dateTimeBetween('now', '+6 months'));
            $details->setEndDate($faker->boolean(70) ? $faker->dateTimeBetween('+1 day', '+6 months') : null);
            
            // Random time between 8 AM and 8 PM
            $startHour = $faker->numberBetween(8, 20);
            $startTime = new \DateTime();
            $startTime->setTime($startHour, 0);
            $details->setStartTime($startTime);

            // End time 2-4 hours after start time
            if ($faker->boolean(80)) {
                $endTime = clone $startTime;
                $endTime->modify('+' . $faker->numberBetween(2, 4) . ' hours');
                $details->setEndTime($endTime);
            }
            
            $details->setPhone($faker->phoneNumber());
            $details->setEmail($faker->email());
            $details->setLocation($faker->address());
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
