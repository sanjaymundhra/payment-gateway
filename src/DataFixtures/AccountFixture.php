<?php
// src/DataFixtures/AccountFixture.php
namespace App\DataFixtures;

use App\Entity\Account;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use App\DataFixtures\UserFixture;

class AccountFixture extends Fixture implements DependentFixtureInterface
{

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }

    public function load(ObjectManager $manager): void 
    {
        $user1 = $this->getReference('user1', User::class);
        $user2 = $this->getReference('user2', User::class);

        $account1 = new Account($user1, $user1->getEmail(), 'USD');
        $account1->setBalance(1000);
        $account1->setOwnerName($user1->getEmail());
        $account1->setCurrency('USD');
        $manager->persist($account1);
        $this->addReference('user1_account', $account1); 

        $account2 = new Account($user2, $user2->getEmail(), 'USD');
        $account2->setBalance(500);
        $account2->setOwnerName($user2->getEmail());
        $account2->setCurrency('USD');
        $manager->persist($account2);
        $this->addReference('user2_account', $account2);

        $manager->flush();
    }
}
