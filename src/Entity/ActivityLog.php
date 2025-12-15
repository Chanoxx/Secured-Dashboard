<?php

namespace App\Entity;

use App\Repository\ActivityLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ✔ Required: User ID
    #[ORM\Column]
    private ?int $userId = null;

    // ✔ Required: Username
    #[ORM\Column(length: 255)]
    private ?string $username = null;

    // ✔ Required: User Role
    #[ORM\Column(length: 50)]
    private ?string $role = null;

    // ✔ Required: Action performed
    #[ORM\Column(length: 255)]
    private ?string $action = null;

    // ✔ Required: Target Data (what was affected)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $targetData = null;

    // ✔ Required: Date & Time
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ---------- GETTERS & SETTERS ----------
    public function getId(): ?int { return $this->id; }

    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }

    public function getAction(): ?string { return $this->action; }
    public function setAction(string $action): self { $this->action = $action; return $this; }

    public function getTargetData(): ?string { return $this->targetData; }
    public function setTargetData(?string $targetData): self { $this->targetData = $targetData; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
}
