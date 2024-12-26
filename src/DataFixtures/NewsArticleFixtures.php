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
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class NewsArticleFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
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
        ],
        [
            'title' => 'Environmental Projects Leading Crowdfunding Success',
            'excerpt' => 'How green initiatives are becoming the most funded projects on crowdfunding platforms.',
            'tags' => ['Environment', 'Sustainability', 'Green Projects']
        ],
        [
            'title' => 'Community-Based Funding Initiatives',
            'excerpt' => 'Local communities are leveraging crowdfunding to bring their projects to life.',
            'tags' => ['Community', 'Local Projects', 'Social Impact']
        ],
        [
            'title' => 'Blockchain and Crowdfunding',
            'excerpt' => 'Exploring the integration of blockchain technology in modern crowdfunding platforms.',
            'tags' => ['Blockchain', 'Technology', 'Innovation']
        ],
        [
            'title' => 'Art and Creative Projects Funding Guide',
            'excerpt' => 'A comprehensive guide for artists seeking crowdfunding success.',
            'tags' => ['Art', 'Creative', 'Funding']
        ],
        [
            'title' => 'Education Projects Through Crowdfunding',
            'excerpt' => 'How educators are using crowdfunding to enhance learning experiences.',
            'tags' => ['Education', 'Learning', 'Schools']
        ],
        [
            'title' => 'Healthcare Innovations via Crowdfunding',
            'excerpt' => 'Medical projects finding success through community support.',
            'tags' => ['Healthcare', 'Medical', 'Innovation']
        ],
        [
            'title' => 'Small Business Crowdfunding Success Stories',
            'excerpt' => 'Local businesses that thrived through crowdfunding support.',
            'tags' => ['Business', 'Success Stories', 'Local']
        ],
        [
            'title' => 'Global Impact of Social Crowdfunding',
            'excerpt' => 'How international communities benefit from crowdfunding initiatives.',
            'tags' => ['Global', 'Social Impact', 'International']
        ]
    ];

    private const AUTHORS = [
        'Sarah Johnson' => 'landing/images/blog/author-1.jpg',
        'Michael Chen' => 'landing/images/blog/author-2.jpg',
        'Emma Thompson' => 'landing/images/blog/author-3.jpg',
        'David Wilson' => 'landing/images/blog/author-4.jpg',
        'Lisa Rodriguez' => 'landing/images/blog/author-5.jpg',
        'James Smith' => 'landing/images/blog/author-6.jpg',
        'Anna Wong' => 'landing/images/blog/author-7.jpg'
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
        $sections[] = $faker->paragraph(4);

        // Key Points
        $sections[] = '<h2>Key Points</h2>';
        for ($i = 0; $i < 3; $i++) {
            $sections[] = sprintf('• %s', $faker->sentence(10));
        }

        // Main content sections
        for ($i = 0; $i < 4; $i++) {
            $sections[] = sprintf('<h3>%s</h3>', $faker->sentence());
            $sections[] = $faker->paragraph(5);
            $sections[] = $faker->paragraph(4);

            // Add some bullet points
            if ($i % 2 == 0) {
                $sections[] = '<ul>';
                for ($j = 0; $j < 3; $j++) {
                    $sections[] = sprintf('<li>%s</li>', $faker->sentence());
                }
                $sections[] = '</ul>';
            }
        }

        // Conclusion
        $sections[] = '<h3>Conclusion</h3>';
        $sections[] = $faker->paragraph(4);
        $sections[] = $faker->paragraph(3);

        return implode("\n\n", array_map(fn($section) => "<p>$section</p>", $sections));
    }

    public function getDependencies(): array
    {
        return [
            NewsCategoryFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['NewsArticleFixtures'];
    }
}
