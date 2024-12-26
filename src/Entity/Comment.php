<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Comment
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?NewsArticle $article = null;

    #[ORM\Column(length: 255)]
    private ?string $author = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    public function __construct()
    {
        $this->setCreatedAtValue();
    }

    #[ORM\PrePersist]
    public function updateArticleCommentsCount(): void
    {
        if ($this->article) {
            $this->article->incrementCommentsCount();
        }
    }

    #[ORM\PreRemove]
    public function decrementArticleCommentsCount(): void
    {
        if ($this->article) {
            $this->article->decrementCommentsCount();
        }
    }

    // Getters and setters with fluent interface
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticle(): ?NewsArticle
    {
        return $this->article;
    }

    public function setArticle(?NewsArticle $article): self
    {
        $this->article = $article;
        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }
}
