<?php
namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransferApiTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the in-memory client
        $this->client = static::createClient();

        // Optionally prepare the test database here
        $em = $this->client->getContainer()->get('doctrine')->getManager();

        // Clean accounts table (or use transactions for rollback)
        $em->getConnection()->executeStatement('DELETE FROM failed_transactions');
        $em->getConnection()->executeStatement('DELETE FROM transactions');
        $em->getConnection()->executeStatement('DELETE FROM accounts');

        // Insert test accounts
        $em->getConnection()->executeStatement(
            "INSERT INTO accounts (id, uuid, account_holder_id, balance, currency, created_at)
             VALUES (1, 'acc-123', 10, 100.00, 'USD', NOW())"
        );
        $em->getConnection()->executeStatement(
            "INSERT INTO accounts (id, uuid, account_holder_id, balance, currency, created_at)
             VALUES (2, 'acc-456', 11, 0.00, 'USD', NOW())"
        );
    }

    public function testSuccessfulTransfer(): void
    {
        // Use Symfony internal client to call API
        $this->client->request(
            'POST',
            '/api/transaction/transactions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'from_account_id' => 1,
                'to_account_id'   => 2,
                'amount'          => 50.00,
                'currency'        => 'USD',
            ])
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('success', $data['status']);
        $this->assertArrayHasKey('uuid', $data);
    }
}
