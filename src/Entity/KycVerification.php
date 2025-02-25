<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\Timestampable;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class KycVerification
{
    use Timestampable;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $referenceId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'json')]
    private array $documentPaths = [];

    #[ORM\Column(type: 'json')]
    private array $submittedData = [];

    #[ORM\Column(type: 'datetime')]
    private \DateTime $submittedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $processedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adminComment = null;

    #[ORM\ManyToOne(inversedBy: 'kycVerifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function setReferenceId(string $referenceId): self
    {
        $this->referenceId = $referenceId;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDocumentPaths(): array
    {
        return $this->documentPaths;
    }

    public function setDocumentPaths(array $documentPaths): self
    {
        $this->documentPaths = $documentPaths;
        return $this;
    }

    public function getSubmittedData(): array
    {
        return $this->submittedData;
    }

    public function setSubmittedData(array $submittedData): self
    {
        $this->submittedData = $submittedData;
        return $this;
    }

    public function getSubmittedAt(): \DateTime
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTime $submittedAt): self
    {
        $this->submittedAt = $submittedAt;
        return $this;
    }

    public function getProcessedAt(): ?\DateTime
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTime $processedAt): self
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    public function getAdminComment(): ?string
    {
        return $this->adminComment;
    }

    public function setAdminComment(?string $adminComment): self
    {
        $this->adminComment = $adminComment;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->getAuthor();
    }

    public function setUser(?User $author): static
    {
        return $this->setAuthor($author);
    }

    public function getDocumentType()
    {
        return $this->getSubmittedData()['documentType'] ?? null;
    }


    public function getDocumentTypeLabel()
    {
        return match($this->getDocumentType()){
            'ENTERPRISE' => 'Document d\'entreprise',
            'ASSOCIATION' => 'Document d\'association',
            'national_id' => 'Carte d\'identité nationale',
            'passport' => 'Passeport',
            'drivers_license' => 'Permis de conduire',
            'residence_permit' => 'Carte de séjour',
            default => 'Inconnu'
        };
    }

    public function getDocuments()
    {
        return $this->getDocumentPaths();
    }

}
