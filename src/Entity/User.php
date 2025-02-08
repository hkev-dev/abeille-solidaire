<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\UserRepository;
use App\Service\DonationService;
use App\Service\FlowerService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;use Doctrine\Common\Collections\Criteria;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableTrait;

    public const ACCOUNT_TYPE_PRIVATE = 'PRIVATE';
    public const ACCOUNT_TYPE_ENTERPRISE = 'ENTERPRISE';
    public const ACCOUNT_TYPE_ASSOCIATION = 'ASSOCIATION';

    public const ACCOUNT_TYPES = [
        self::ACCOUNT_TYPE_PRIVATE,
        self::ACCOUNT_TYPE_ENTERPRISE,
        self::ACCOUNT_TYPE_ASSOCIATION,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = "";

    #[ORM\Column(length: 255)]
    private ?string $lastName = "";

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[Vich\UploadableField(mapping: 'user_avatars', fileNameProperty: 'avatar')]
    private ?File $avatarFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 20)]
    private ?string $phone = "";

    #[ORM\Column(length: 2)]
    private ?string $country = "";

    #[ORM\Column(length: 20)]
    private string $accountType = self::ACCOUNT_TYPE_PRIVATE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $organizationName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $organizationNumber = null;

    #[ORM\OneToOne(targetEntity: Project::class, mappedBy: 'creator')]
    private ?Project $project = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $walletBalance = 0.0;

    #[ORM\OneToMany(targetEntity: Donation::class, mappedBy: 'donor')]
    private Collection $donationsMade;

    #[ORM\OneToMany(targetEntity: Donation::class, mappedBy: 'recipient')]
    private Collection $donationsReceived;

    #[ORM\OneToMany(targetEntity: Withdrawal::class, mappedBy: 'user')]
    private Collection $withdrawals;

    #[ORM\OneToMany(targetEntity: Membership::class, mappedBy: 'user')]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $memberships;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $waitingSince = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isKycVerified = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $kycVerifiedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $defaultPaymentMethodId = null;

    public function __construct()
    {
        $this->donationsMade = new ArrayCollection();
        $this->donationsReceived = new ArrayCollection();
        $this->withdrawals = new ArrayCollection();
        $this->memberships = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return trim(sprintf('%s %s', $this->firstName ?? '', $this->lastName ?? ''));
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        if ($project === null && $this->project !== null) {
            $this->project->setCreator(null);
        }

        if ($project !== null && $project->getCreator() !== $this) {
            $project->setCreator($this);
        }

        $this->project = $project;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getFullName(): string
    {
        return $this->getName();
    }

    public function getAvatarFile(): ?File
    {
        return $this->avatarFile;
    }

    public function setAvatarFile(?File $avatarFile = null): void
    {
        $this->avatarFile = $avatarFile;
        if ($avatarFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getWalletBalance(): float
    {
        $balance = 0.0;

        /** @var Donation $donation */
        foreach ($this->donationsMade as $donation) {
            $balance += $donation->getEarningsAmount();
        }

        return $balance;
    }

    public function setWalletBalance(float $walletBalance): self
    {
        $this->walletBalance = $walletBalance;
        return $this;
    }

    public function addToWalletBalance(float $amount): self
    {
        $this->walletBalance += $amount;
        return $this;
    }

    public function getCurrentFlower(): ?Flower
    {
        return $this->getMainDonation()?->getFlower();
    }

    public function setCurrentFlower(?Flower $flower): self
    {
        return $this;
    }

    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function getCurrentMembership(): ?Membership
    {
        $now = new \DateTime();

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('status', Membership::STATUS_ACTIVE))
            ->andWhere(Criteria::expr()->lte('startDate', $now))
            ->andWhere(Criteria::expr()->gt('endDate', $now))
            ->orderBy(['startDate' => Order::Descending]);

        $membershipFiltered = $this->memberships->matching($criteria);

        return $membershipFiltered->first() ?: null;
    }

    public function hasPaidAnnualFee(): bool
    {
        if (array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $this->getRoles())) {
            return true;
        }

        $currentMembership = $this->getCurrentMembership();
        if (!$currentMembership) {
            return false;
        }

        return !$currentMembership->isExpired();
    }

    public function getLastMembership(): ?Membership
    {
        return $this->memberships->first() ?: null;
    }

    public function addMembership(Membership $membership): self
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
            $membership->setUser($this);
        }
        return $this;
    }

    public function removeMembership(Membership $membership): self
    {
        if ($this->memberships->removeElement($membership)) {
            // We might want to change the status to expired instead of removing the relationship
            // since we can't set the user to null (it's a required field)
            $membership->setStatus(Membership::STATUS_EXPIRED);
        }
        return $this;
    }

    public function getAccountType(): string
    {
        return $this->accountType;
    }

    public function getDaysUntilAnnualFeeExpiration(): ?int
    {
        if (array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $this->getRoles())) {
            return null; // Admin users don't expire
        }

        $currentMembership = $this->getCurrentMembership();
        if (!$currentMembership) {
            return 0;
        }

        return $currentMembership->getDaysUntilExpiration();
    }

    public function getMembershipExpiredAt(): ?\DateTimeInterface
    {
        if (array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $this->getRoles())) {
            return null; // Admin users don't expire
        }

        $currentMembership = $this->getCurrentMembership();
        return $currentMembership ? $currentMembership->getEndDate() : null;
    }

    public function getDonationsMade(): Collection
    {
        return $this->donationsMade;
    }

    public function getDonationsReceived(): Collection
    {
        return $this->donationsReceived;
    }

    public function getWithdrawals(): Collection
    {
        return $this->withdrawals;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getWaitingSince(): ?\DateTimeInterface
    {
        return $this->waitingSince;
    }

    public function setWaitingSince(?\DateTimeInterface $waitingSince): self
    {
        $this->waitingSince = $waitingSince;
        return $this;
    }

    public function isKycVerified(): bool
    {
        return $this->isKycVerified;
    }

    public function setIsKycVerified(bool $isKycVerified): self
    {
        $this->isKycVerified = $isKycVerified;
        if ($isKycVerified) {
            $this->kycVerifiedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getKycVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->kycVerifiedAt;
    }

    public function setKycVerifiedAt(?\DateTimeImmutable $kycVerifiedAt): self
    {
        $this->kycVerifiedAt = $kycVerifiedAt;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function setAccountType(string $accountType): self
    {
        if (!in_array($accountType, self::ACCOUNT_TYPES)) {
            throw new \InvalidArgumentException('Invalid account type');
        }
        $this->accountType = $accountType;
        return $this;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setOrganizationName(?string $organizationName): self
    {
        $this->organizationName = $organizationName;
        return $this;
    }

    public function getOrganizationNumber(): ?string
    {
        return $this->organizationNumber;
    }

    public function setOrganizationNumber(?string $organizationNumber): self
    {
        $this->organizationNumber = $organizationNumber;
        return $this;
    }

    public function hasProject(): bool
    {
        return $this->project !== null;
    }

    // Payment-related methods
    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): self
    {
        $this->stripeCustomerId = $stripeCustomerId;
        return $this;
    }

    public function getDefaultPaymentMethodId(): ?string
    {
        return $this->defaultPaymentMethodId;
    }

    public function setDefaultPaymentMethodId(?string $defaultPaymentMethodId): self
    {
        $this->defaultPaymentMethodId = $defaultPaymentMethodId;
        return $this;
    }

    public function isEligibleForWithdrawal(): bool
    {
        // Admin users bypass matrix depth check
        $hasRequiredMatrixDepth = array_intersect(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $this->getRoles()) ||
            $this->getMainDonation()?->getMatrixDepth() >= 3;

        return $this->isKycVerified &&      // KYC verification completed
            $this->hasPaidAnnualFee() &&    // Annual membership is active
            $hasRequiredMatrixDepth &&       // Has required matrix depth
            $this->hasProject();             // Has at least one project
    }

    public function getFlowerProgress(): array
    {
        $flowerReceived = $this->countCurrentFlowerChildren();
        $flowerNumberOfSlot = $this->getCurrentFlower()->getNumberOfSlots();
        return [
            'received' => $flowerReceived,
            'total' => $flowerNumberOfSlot,
            'percentage' => ($flowerReceived / $flowerNumberOfSlot) * 100,
            'remaining' => max(0, $flowerNumberOfSlot - $flowerReceived)
        ];
    }

    public function countCurrentFlowerChildren(): int
    {
        $previousChildren = 0;
        for ($i = 1; $i < $this->getCurrentFlower()->getLevel(); $i++) {
            $previousChildren += FlowerService::getNumberOfSlotByLevel($i);
        }

        return $this->getMatrixChildrenCount() - $previousChildren;
    }

    public function countDirectChildrens(): int
    {
        return $this->getMainDonation()->countDirectChildrens();
    }

    public function countDonations(): int
    {
        return $this->getDonationsMade()->count();
    }

    public function getTotalReceivedInFlower(): float
    {
        $total = 0.0;
        foreach ($this->donationsReceived as $donation) {
            if ($donation->getFlower() === $this->getCurrentFlower()) {
                $total += $donation->getAmount();
            }
        }
        return $total;
    }

    public function getReceivedAmountInCurrentFlower(): float
    {
        $total = 0.0;
        /** @var Donation $donation */
        foreach ($this->getDonationsMade() as $donation) {
            $total = $donation->getEarnings()->filter(function (Earning $earning) {
                return $earning->getFlower() === $this->getCurrentFlower();
            })->reduce(function (float $carry, Earning $earning) use ($total) {
                return $carry + $earning->getAmount();
            }, $total);
        }

        return $total;
    }

    public function getReceivedAmount(): float
    {
        $total = 0.0;
        /** @var Donation $donation */
        foreach ($this->getDonationsMade() as $donation) {
            $total = $donation->getEarnings()->reduce(function (float $carry, Earning $earning) use ($total) {
                return $carry + $earning->getAmount();
            }, $total);
        }

        return $total;
    }

    public function getTotalReceivedInCurrentCycle(): float
    {
        $total = 0.0;
        $cycleStart = new \DateTime();

        // Get the last solidarity donation to determine cycle start
        $lastSolidarity = $this->donationsReceived
            ->filter(fn($d) => $d->getDonationType() === 'solidarity')
            ->last();

        if ($lastSolidarity) {
            $cycleStart = $lastSolidarity->getTransactionDate();
        }

        foreach ($this->donationsReceived as $donation) {
            if (
                $donation->getFlower() === $this->getCurrentFlower() &&
                $donation->getTransactionDate() > $cycleStart
            ) {
                $total += $donation->getAmount();
            }
        }
        return $total;
    }

    public function getMainDonation(): ?Donation
    {
        $results = $this->getDonationsMade()->filter(function(Donation $donation) {
            return $donation->getPaymentStatus() === Donation::PAYMENT_COMPLETED;
        })
            ->matching(
                Criteria::create()->orderBy(['paymentCompletedAt' => Order::Ascending]) // Ou DESC selon le besoin
            );

        return $results->first() ?: null;
    }

    public function getMatrixDepth(): int
    {
        return $this->getMainDonation()->getMatrixDepth();
    }

    public function getMatrixLevel(): int
    {
        return $this->getMainDonation()->getMatrixLevel();
    }

    public function getMatrixPosition(): ?int
    {
        return $this->getMainDonation()->getMatrixPosition();
    }

    public function getParent(): ?self
    {
        return $this->getMainDonation()->getParent()?->getDonor();
    }

    public function getChildren(): Collection
    {
        $childrens = $this->getMainDonation()->getChildrens()->toArray();

        usort($childrens, fn(Donation $a, Donation $b) => $a->getMatrixPosition() <=> $b->getMatrixPosition());

        return (new ArrayCollection($childrens))->map(function (Donation $donation) {
            return $donation->getDonor();
        });
    }

    public function getMatrixChildrenCount(): int
    {
        if ($this->getMainDonation()->getPaymentStatus() === Donation::PAYMENT_COMPLETED) {
            return $this->getMainDonation()->countAllChildrens();
        }

        return 0;
    }

    public function getAccountTypeLabel(): string
    {
        return match ($this->accountType) {
            self::ACCOUNT_TYPE_PRIVATE => 'Privée',
            self::ACCOUNT_TYPE_ENTERPRISE => 'Entreprise',
            self::ACCOUNT_TYPE_ASSOCIATION => 'Association',
            default => 'Inconnu'
        };
    }
}
