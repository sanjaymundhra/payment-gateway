<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="transactions", indexes={@ORM\Index(columns={"idempotency_key"})})
 */
class Transfer
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="bigint") */
    private ?int $id = null;

    /** @ORM\Column(type="string", length=36, unique=true) */
    private string $uuid;

    /** @ORM\Column(type="bigint") */
    private int $fromAccountId;

    /** @ORM\Column(type="bigint") */
    private int $toAccountId;

    /** @ORM\Column(type="decimal", precision=20, scale=4) */
    private string $amount;

    /** @ORM\Column(type="string", length=3) */
    private string $currency;

    /** @ORM\Column(type="string", length=20) */
    private string $status = 'pending';

    /** @ORM\Column(type="string", length=255, nullable=true) */
    private ?string $idempotencyKey = null;

    /** @ORM\Column(type="datetime_immutable") */
    private \DateTimeImmutable $createdAt;

    public function __construct(string $uuid, int $fromId, int $toId, string $amount, string $currency, ?string $idempotencyKey = null)
    {
        $this->uuid = $uuid;
        $this->fromAccountId = $fromId;
        $this->toAccountId = $toId;
        $this->amount = $amount;
        $this->currency = strtoupper($currency);
        $this->idempotencyKey = $idempotencyKey;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getFromAccountId(): int { return $this->fromAccountId; }
    public function getToAccountId(): int { return $this->toAccountId; }
    public function getAmount(): string { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $s): self { $this->status = $s; return $this; }
    public function getIdempotencyKey(): ?string { return $this->idempotencyKey; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
