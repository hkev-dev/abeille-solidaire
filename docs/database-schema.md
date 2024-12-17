# Database Schema Documentation

## Overview
This document outlines the database schema for the Qrowd platform, including both public-facing content and back office administration.

## Entity Categories

### User Management
- **User**: Core user entity with authentication and roles
- **Address**: User address information

### Content Management
- **Project**: Main project entity
- **Category**: Project categories
- **ProjectUpdate**: Project progress updates
- **ProjectReview**: User reviews for projects
- **NewsArticle**: Blog/news content
- **Comment**: Comments on news articles
- **Event**: Platform events
- **TeamMember**: Team member profiles
- **Testimonial**: User testimonials
- **FAQ**: Frequently asked questions

### Site Configuration
- **Setting**: Key-value settings
- **SiteConfig**: Global site configuration
- **MainSlider**: Homepage slider content
- **BrandPartner**: Partner/sponsor logos

### Communication
- **Contact**: Contact form submissions
- **Newsletter**: Newsletter subscriptions

## Entity Definitions

### Core Entities

#### User
```php
#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;
}
```

#### Setting
```php
#[ORM\Entity]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $key = null;

    #[ORM\Column(type: 'text')]
    private ?string $value = null;
}
```

### Project Related Entities

#### Category
```php
#[ORM\Entity]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $icon = null;

    #[ORM\Column]
    private ?int $projectCount = 0;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Project::class)]
    private Collection $projects;
}
```

#### Project
```php
#[ORM\Entity]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?Category $category = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column]
    private ?float $goal = 0;

    #[ORM\Column]
    private ?int $progress = 0;

    #[ORM\Column(type: 'text')]
    private ?string $story = null;

    #[ORM\Column(type: 'json')]
    private array $storyContent = [];

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectUpdate::class)]
    private Collection $updates;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectReview::class)]
    private Collection $reviews;

    #[ORM\Column]
    private ?int $backersCount = 0;

    #[ORM\Column]
    private ?float $pledged = 0;

    #[ORM\Column]
    private ?int $remainingDays = null;
}
```

#### ProjectUpdate
```php
#[ORM\Entity]
class ProjectUpdate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'updates')]
    private ?Project $project = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;
}
```

#### ProjectReview
```php
#[ORM\Entity]
class ProjectReview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    private ?Project $project = null;

    #[ORM\Column(length: 255)]
    private ?string $author = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column]
    private ?int $rating = null;

    #[ORM\Column(type: 'text')]
    private ?string $comment = null;
}
```

### Content Entities

#### TeamMember
```php
#[ORM\Entity]
class TeamMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $position = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(type: 'json')]
    private array $socialLinks = [];
}
```

#### Testimonial
```php
#[ORM\Entity]
class Testimonial
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $position = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(type: 'text')]
    private ?string $text = null;

    #[ORM\Column]
    private ?int $rating = null;
}
```

#### FAQ
```php
#[ORM\Entity]
class Faq
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $question = null;

    #[ORM\Column(type: 'text')]
    private ?string $answer = null;

    #[ORM\Column(length: 255)]
    private ?string $category = null;

    #[ORM\Column]
    private ?bool $isActive = true;
}
```

#### Event
```php
#[ORM\Entity]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255)]
    private ?string $time = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'json')]
    private array $details = [];
}
```

#### NewsArticle
```php
#[ORM\Entity]
class NewsArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(length: 255)]
    private ?string $author = null;

    #[ORM\Column]
    private ?int $commentsCount = 0;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'json')]
    private array $tags = [];

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: Comment::class)]
    private Collection $comments;
}
```

#### Comment
```php
#[ORM\Entity]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    private ?NewsArticle $article = null;

    #[ORM\Column(length: 255)]
    private ?string $author = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;
}
```

### Site Configuration Entities

#### SiteConfig
```php
#[ORM\Entity]
class SiteConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $siteName = null;

    #[ORM\Column(length: 255)]
    private ?string $logo = null;

    #[ORM\Column(length: 255)]
    private ?string $footerLogo = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(type: 'text')]
    private ?string $footerAbout = null;

    #[ORM\Column(type: 'json')]
    private array $socialLinks = [];
}
```

#### MainSlider
```php
#[ORM\Entity]
class MainSlider
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(length: 255)]
    private ?string $subtitle = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column]
    private ?int $position = null;
}
```

#### BrandPartner
```php
#[ORM\Entity]
class BrandPartner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;
}
```

### Communication Entities

#### Contact
```php
#[ORM\Entity]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;
}
```

#### Newsletter
```php
#[ORM\Entity]
class Newsletter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $subscribedAt = null;

    #[ORM\Column]
    private ?bool $isActive = true;
}
```

## Technical Details

All entities include:
- Auto-generated IDs
- Proper relationships between entities
- Support for media uploads (images)
- SEO-friendly slugs where needed
- Timestamps for creation/updates where appropriate

## Capabilities

This structure enables:
1. Complete content management through admin interface
2. User role and permission management
3. Project creation and management
4. News/blog functionality
5. Contact and newsletter management
6. Site configuration and customization
7. Search functionality tracking