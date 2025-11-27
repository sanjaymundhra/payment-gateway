<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'transactions')]
class Transaction
{

    public const TYPE_DEBIT = 'debit';
    public const TYPE_CREDIT = 'credit';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(name: 'account_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Account $account;

    #[ORM\Column(type: 'string', length: 6)]
    private string $type;  // "debit" or "credit"

    #[ORM\Column(type: 'decimal', precision: 20, scale: 4)]
    private string $amount;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Account $account, string $type, string $amount, string $currency)
    {
        $this->uuid = uuid_create(UUID_TYPE_RANDOM);
        $this->createdAt = new \DateTimeImmutable();
        $this->account = $account;
        $this->type = $type;
        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }

    public function getId(): ?int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getAccount(): Account { return $this->account; }
    public function getType(): string { return $this->type; }
    public function getAmount(): string { return $this->amount; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getCurrency(): string { return $this->currency; }
}
