<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'accounts')]
class Account
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $ownerName;

    #[ORM\Column(type: 'decimal', precision: 20, scale: 4)]
    private string $balance = '0.0000';

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency = 'INR';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, string $ownerName, string $currency = 'INR')
    {
        $this->user = $user;
        $this->ownerName = $ownerName;
        $this->currency = strtoupper($currency);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getOwnerName(): string { return $this->ownerName; }
    public function setOwnerName(string $name): self { $this->ownerName = $name; return $this; }

    public function getBalance(): string { return $this->balance; }
    public function setBalance(string $balance): self { $this->balance = $balance; return $this; }

    public function getCurrency(): string { return $this->currency; }
    public function setCurrency(string $currency): self { $this->currency = $currency; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }

    public function increase(string $amount): void { $this->balance = bcadd($this->balance, $amount, 4); }
    public function decrease(string $amount): void { $this->balance = bcsub($this->balance, $amount, 4); }
}
