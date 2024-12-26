<?php

namespace App\DataFixtures;

use App\Entity\NewsArticle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Entity\NewsCategory;

class NewsArticleFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private KernelInterface $kernel,
    ) {}

    private const ARTICLES = [
        [
            'title' => 'How Crowdfunding Is Changing The World',
            'excerpt' => 'Discover the revolutionary impact of crowdfunding on global innovation and social causes.',
            'tags' => ['Crowdfunding', 'Innovation', 'Social Impact']
        ],
        [
            'title' => '10 Successful Crowdfunding Campaign Strategies',
            'excerpt' => 'Learn from the most successful crowdfunding campaigns and their winning strategies.',
            'tags' => ['Strategy', 'Tips', 'Success Stories']
        ],
        [
            'title' => 'The Future of Social Innovation Funding',
            'excerpt' => 'Exploring how social innovation projects are leveraging crowdfunding platforms.',
            'tags' => ['Social Innovation', 'Future', 'Technology']
        ],
        [
            'title' => 'Understanding Crowdfunding Regulations',
            'excerpt' => 'A comprehensive guide to navigating crowdfunding regulations and compliance.',
            'tags' => ['Regulations', 'Legal', 'Guidelines']
        ],
        [
            'title' => 'Impact of Technology on Crowdfunding',
            'excerpt' => 'How technological advances are shaping the future of crowdfunding platforms.',
            'tags' => ['Technology', 'Innovation', 'Digital']
        ]
    ];

    private const AUTHORS = [
        'Sarah Johnson' => 'landing/images/blog/author-1.jpg',
        'Michael Chen' => 'landing/images/blog/author-2.jpg',
        'Emma Thompson' => 'landing/images/blog/author-3.jpg',
        'David Wilson' => 'landing/images/blog/author-4.jpg'
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        foreach (self::ARTICLES as $index => $articleData) {
            $article = new NewsArticle();
            $article
                ->setTitle($articleData['title'])
                ->setExcerpt($articleData['excerpt'])
                ->setContent($this->generateContent($faker))
                ->setAuthor(array_rand(self::AUTHORS))
                ->setTags($articleData['tags'])
                ->setCommentsCount(0)
                // Fix: Add NewsCategory::class as second parameter to getReference
                ->setCategory($this->getReference('news_category_' . $faker->numberBetween(0, 4), NewsCategory::class));

            // Handle image upload - cycle through existing images
            $imageNumber = ($index % 6) + 1;
            $sourceImage = "news-1-{$imageNumber}.jpg";

            $sourcePath = $this->kernel->getProjectDir() . '/assets/landing/images/blog/' . $sourceImage;
            if (file_exists($sourcePath)) {
                $uploadDir = $this->kernel->getProjectDir() . '/public/uploads/news';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $tempFile = tempnam(sys_get_temp_dir(), 'news');
                copy($sourcePath, $tempFile);

                $uploadedFile = new UploadedFile(
                    $tempFile,
                    $sourceImage,
                    'image/jpeg',
                    null,
                    true
                );

                $article->setImageFile($uploadedFile);
            }

            $manager->persist($article);
            $this->addReference('article_' . $index, $article);
        }

        $manager->flush();
    }

    private function generateContent(\Faker\Generator $faker): string
    {
        $sections = [];

        // Introduction
        $sections[] = $faker->paragraph(3);

        // Main content sections
        for ($i = 0; $i < 3; $i++) {
            $sections[] = sprintf('<h3>%s</h3>', $faker->sentence());
            $sections[] = $faker->paragraph(4);
            $sections[] = $faker->paragraph(3);
        }

        // Conclusion
        $sections[] = '<h3>Conclusion</h3>';
        $sections[] = $faker->paragraph(3);

        return implode("\n\n", array_map(fn($section) => "<p>$section</p>", $sections));
    }

    public function getDependencies(): array
    {
        return [
            NewsCategoryFixtures::class,
        ];
    }
}
