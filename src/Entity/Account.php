<?php
namespace App\Entity;
use Symfony\Component\Uid\Uuid;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\AccountRepository;

#[ORM\Table(name: 'accounts')]
#[ORM\Entity(repositoryClass: AccountRepository::class)]
class Account
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

     #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    // Updated property name and join column
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'account_holder_id', referencedColumnName: 'id', nullable: false, options: ['unsigned' => true])]
    private ?User $user = null;

    #[ORM\Column(type: 'decimal', precision: 20, scale: 4)]
    private string $balance = '0.0000';

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency = 'INR';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, string $currency = 'INR')
    {
        $this->uuid = Uuid::v4()->toRfc4122();
        $this->user = $user;
        $this->currency = strtoupper($currency);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

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
