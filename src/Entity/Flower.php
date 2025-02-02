<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\FlowerRepository;
use App\Service\DonationService;
use App\Service\FlowerService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlowerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Flower
{
    use TimestampableTrait;

    public const VIOLETTE = 1;
    public const COQUELICOT = 2;
    public const BOUTON_OR = 3;
    public const LAURIER_ROSE = 4;
    public const TULIPE = 5;
    public const GERMINI = 6;
    public const LYS = 7;
    public const CLEMATITE = 8;
    public const CHRYSANTHEME = 9;
    public const ROSE_GOLD = 10;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $donationAmount;

    #[ORM\Column(type: 'integer')]
    private int $level;

    #[ORM\OneToMany(targetEntity: Donation::class, mappedBy: 'flower')]
    private Collection $donations;

    /**
     * @var Collection<int, Earning>
     */
    #[ORM\OneToMany(targetEntity: Earning::class, mappedBy: 'flower')]
    private Collection $earnings;

    public function __construct()
    {
        $this->donations = new ArrayCollection();
        $this->earnings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDonationAmount(): float
    {
        return $this->donationAmount;
    }

    public function setDonationAmount(float $donationAmount): self
    {
        $this->donationAmount = $donationAmount;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function getDonations(): Collection
    {
        return $this->donations;
    }


    public function getMatrixPosition(): int
    {
        return match ($this->level) {
            self::VIOLETTE => 1,
            self::COQUELICOT => 2,
            self::BOUTON_OR => 3,
            self::LAURIER_ROSE => 4,
            self::TULIPE => 5,
            self::GERMINI => 6,
            self::LYS => 7,
            self::CLEMATITE => 8,
            self::CHRYSANTHEME => 9,
            self::ROSE_GOLD => 10,
            default => 0,
        };
    }

    public static function getFlowerByPosition(int $position): string
    {
        return match ($position) {
            1 => 'Violette',
            2 => 'Coquelicot',
            3 => 'Bouton d\'Or',
            4 => 'Laurier Rose',
            5 => 'Tulipe',
            6 => 'Germini',
            7 => 'Lys',
            8 => 'Clématite',
            9 => 'Chrysanthème',
            10 => 'Rose Gold',
            default => '',
        };
    }

    public function isCurrentUserFlower(User $user): bool
    {
        return $user->getCurrentFlower() === $this;
    }

    public static function getAllFlowers(): array
    {
        return [
            self::VIOLETTE => [
                'name' => 'Violette',
                'amount' => 25
            ],
            self::COQUELICOT => [
                'name' => 'Coquelicot',
                'amount' => 50
            ],
            self::BOUTON_OR => [
                'name' => 'Bouton d\'Or',
                'amount' => 100
            ],
            self::LAURIER_ROSE => [
                'name' => 'Laurier Rose',
                'amount' => 200
            ],
            self::TULIPE => [
                'name' => 'Tulipe',
                'amount' => 400
            ],
            self::GERMINI => [
                'name' => 'Germini',
                'amount' => 800
            ],
            self::LYS => [
                'name' => 'Lys',
                'amount' => 1600
            ],
            self::CLEMATITE => [
                'name' => 'Clématite',
                'amount' => 3200
            ],
            self::CHRYSANTHEME => [
                'name' => 'Chrysanthème',
                'amount' => 6400
            ],
            self::ROSE_GOLD => [
                'name' => 'Rose Gold',
                'amount' => 12800
            ]
        ];
    }

    public function getNumberOfSlots(): int
    {
        return FlowerService::getNumberOfSlotByLevel($this->getLevel());
    }

    public function getTotalAmountExpected(): float
    {
        return FlowerService::getLevelTotalAmountExpected($this->getLevel());
    }

    public function getExpectedEarning(): float
    {
        return FlowerService::getLevelExpectedEarning($this->getLevel());
    }

    /**
     * @return Collection<int, Earning>
     */
    public function getEarnings(): Collection
    {
        return $this->earnings;
    }

    public function addEarning(Earning $earning): static
    {
        if (!$this->earnings->contains($earning)) {
            $this->earnings->add($earning);
            $earning->setFlower($this);
        }

        return $this;
    }

    public function removeEarning(Earning $earning): static
    {
        if ($this->earnings->removeElement($earning)) {
            // set the owning side to null (unless already changed)
            if ($earning->getFlower() === $this) {
                $earning->setFlower(null);
            }
        }

        return $this;
    }
}
