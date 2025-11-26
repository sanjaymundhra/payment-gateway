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
        // Get users from references
        /** @var User $user1 */
        $user1 = $this->getReference('user1', User::class);
        /** @var User $user2 */
        $user2 = $this->getReference('user2', User::class);

        // Create first account
        $account1 = new Account($user1, 'USD'); // currency = USD
        $account1->setBalance('1000.0000'); // string with decimals
        $manager->persist($account1);
        $this->addReference('user1_account', $account1); 

        // Create second account
        $account2 = new Account($user2, 'USD');
        $account2->setBalance('500.0000');
        $manager->persist($account2);
        $this->addReference('user2_account', $account2);

        $manager->flush();
    }
}
