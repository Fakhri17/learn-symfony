<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DataSourceLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DataSourceLogRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'data_source_log')]
class DataSourceLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $source = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $totalSuccess = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $totalPartial = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $totalFailed = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->startedAt ??= new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTotalSuccess(): int
    {
        return $this->totalSuccess;
    }

    public function setTotalSuccess(int $totalSuccess): static
    {
        $this->totalSuccess = $totalSuccess;

        return $this;
    }

    public function getTotalPartial(): int
    {
        return $this->totalPartial;
    }

    public function setTotalPartial(int $totalPartial): static
    {
        $this->totalPartial = $totalPartial;

        return $this;
    }

    public function getTotalFailed(): int
    {
        return $this->totalFailed;
    }

    public function setTotalFailed(int $totalFailed): static
    {
        $this->totalFailed = $totalFailed;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }
}