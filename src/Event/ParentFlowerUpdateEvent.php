<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class ParentFlowerUpdateEvent extends Event
{
    public const NAME = 'parent.flower.update';

    private User $parent;
    private array $children;

    public function __construct(User $parent)
    {
        $this->parent = $parent;
        $this->children = $parent->getChildren()->toArray();
    }

    public function getParent(): User
    {
        return $this->parent;
    }

    public function getChildren(): array
    {
        return $this->children;
    }
}
