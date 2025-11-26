<?php

namespace App\Repository;

use App\Entity\Account;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    // Example custom method
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->andWhere('u.id = :uid')
            ->setParameter('uid', $userId)
            ->getQuery()
            ->getResult();
    }
}
