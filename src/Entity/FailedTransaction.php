<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "failed_transactions")]
class FailedTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $transactionId;

    #[ORM\Column(type: 'json')]
    private array $errorMessage = [];

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $failedAt;

    public function __construct()
    {
        $this->failedAt = new \DateTime();
    }

    // ---------------------------
    // Getters and Setters
    // ---------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    public function setTransactionId(int $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    public function getErrorMessage(): array
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(array $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getFailedAt(): \DateTimeInterface
    {
        return $this->failedAt;
    }

    public function setFailedAt(\DateTimeInterface $failedAt): self
    {
        $this->failedAt = $failedAt;
        return $this;
    }
}
