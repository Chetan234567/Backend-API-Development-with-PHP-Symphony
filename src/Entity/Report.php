<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ReportRepository;
use App\Entity\User;
use App\Entity\Post;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'report')]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $reportedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $reportedUser = null;

    #[ORM\ManyToOne(targetEntity: Post::class)]
    private ?Post $reportedPost = null;

    #[ORM\Column(type: 'text')]
    private ?string $reason = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int { return $this->id; }

    public function getReportedBy(): ?User { return $this->reportedBy; }
    public function setReportedBy(User $user): self { $this->reportedBy = $user; return $this; }

    public function getReportedUser(): ?User { return $this->reportedUser; }
    public function setReportedUser(?User $user): self { $this->reportedUser = $user; return $this; }

    public function getReportedPost(): ?Post { return $this->reportedPost; }
    public function setReportedPost(?Post $post): self { $this->reportedPost = $post; return $this; }

    public function getReason(): ?string { return $this->reason; }
    public function setReason(string $reason): self { $this->reason = $reason; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
