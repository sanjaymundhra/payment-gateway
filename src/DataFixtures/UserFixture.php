<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Example user 1
        $user1 = new User();
        $user1->setEmail('user1@example.com');
        $user1->setPassword(
            $this->passwordHasher->hashPassword($user1, 'password123')
        );
        $this->addReference('user1', $user1);
        $manager->persist($user1);

        // Example user 2
        $user2 = new User();
        $user2->setEmail('user2@example.com');
        $user2->setPassword(
            $this->passwordHasher->hashPassword($user2, 'password456')
        );
        $this->addReference('user2', $user2);
        $manager->persist($user2);

        $manager->flush();
    }
}
