<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\SystemConfigurationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SystemConfigurationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SystemConfiguration
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private string $key;

    #[ORM\Column(type: 'text')]
    private string $value;

    #[ORM\Column(length: 255)]
    private string $type = 'string';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public const TYPES = ['string', 'integer', 'float', 'boolean', 'json'];

    public const KEY_WITHDRAWAL_FEE = 'withdrawal_fee';
    public const KEY_MIN_WITHDRAWAL = 'min_withdrawal';
    public const KEY_MAX_WITHDRAWAL = 'max_withdrawal_per_week';
    public const KEY_MEMBERSHIP_FEE = 'annual_membership_fee';
    public const KEY_REGISTRATION_FEE = 'registration_fee';
    public const KEY_WAITING_ROOM_EXPIRY_DAYS = 'waiting_room_expiry_days';
    public const KEY_MATRIX_GRACE_PERIOD_DAYS = 'matrix_grace_period_days';
    public const KEY_MAX_MATRIX_DEPTH = 'max_matrix_depth';
    public const KEY_MIN_MATRIX_DEPTH_FOR_WITHDRAWAL = 'min_matrix_depth_for_withdrawal';

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!in_array($type, self::TYPES)) {
            throw new \InvalidArgumentException('Invalid configuration type');
        }
        $this->type = $type;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getTypedValue(): mixed
    {
        return match($this->type) {
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }
}
