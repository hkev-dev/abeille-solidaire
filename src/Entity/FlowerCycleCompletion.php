<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\FlowerCycleCompletionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlowerCycleCompletionRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(columns: ['user_id', 'flower_id'])]
class FlowerCycleCompletion
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'flowerCycleCompletions')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Flower::class, inversedBy: 'cycleCompletions')]
    #[ORM\JoinColumn(nullable: false)]
    private Flower $flower;

    #[ORM\Column(type: 'integer')]
    private int $completionCount = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getFlower(): Flower
    {
        return $this->flower;
    }

    public function setFlower(Flower $flower): self
    {
        $this->flower = $flower;
        return $this;
    }

    public function getCompletionCount(): int
    {
        return $this->completionCount;
    }

    public function setCompletionCount(int $count): self
    {
        $this->completionCount = $count;
        return $this;
    }

    public function incrementCompletionCount(): self
    {
        $this->completionCount++;
        return $this;
    }

    public function hasReachedLimit(): bool
    {
        return $this->completionCount >= 10;
    }
}
