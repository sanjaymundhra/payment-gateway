<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use App\Entity\Transaction;

class TransactionService
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private $redis;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, $redis)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->redis = $redis;
    }

    /**
     * Execute a transfer.
     *
     * @param int $fromAccountId
     * @param int $toAccountId
     * @param string $amount decimal string
     * @param string $currency 3-letter code
     * @param string|null $idempotencyKey
     * @return array ['status' => 'completed'|'failed', 'uuid'=>string]
     * @throws \Throwable
     */
    public function transfer(int $fromAccountId, int $toAccountId, string $amount, string $currency, ?string $idempotencyKey = null): array
    {
        if (bccomp($amount, '0', 4) <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        // Idempotency: check existing transfer by key
        if ($idempotencyKey) {
            $existing = $this->em->getRepository(Transaction::class)->findOneBy(['idempotencyKey' => $idempotencyKey]);
            if ($existing) {
                return ['status' => $existing->getStatus(), 'uuid' => $existing->getUuid()];
            }
        }

        // Optional redis lock to avoid concurrent work across nodes
        $lockKey = 'transfer_lock:' . $fromAccountId;
        $acquired = false;
        try {
            // Predis set with NX & EX or php-redis equivalent
            $acquired = (bool) $this->redis->set($lockKey, 1, ['nx' => true, 'ex' => 10]);
        } catch (\Throwable $e) {
            $this->logger->warning('Redis lock unavailable: ' . $e->getMessage());
            // continue; DB locking still protects correctness
        }

        try {
            $conn = $this->em->getConnection();
            $result = $conn->transactional(function($conn) use ($fromAccountId, $toAccountId, $amount, $currency, $idempotencyKey) {
                // SELECT FOR UPDATE ensures row-level lock
                $from = $conn->fetchAssociative('SELECT * FROM accounts WHERE id = ? FOR UPDATE', [$fromAccountId]);
                if (!$from) throw new \RuntimeException('From account not found');

                $to = $conn->fetchAssociative('SELECT * FROM accounts WHERE id = ? FOR UPDATE', [$toAccountId]);
                if (!$to) throw new \RuntimeException('To account not found');

                if ($from['currency'] !== strtoupper($currency) || $to['currency'] !== strtoupper($currency)) {
                    throw new \RuntimeException('Currency mismatch');
                }

                if (bccomp($from['balance'], $amount, 4) < 0) {
                    throw new \RuntimeException('Insufficient funds');
                }

                $newFrom = bcsub($from['balance'], $amount, 4);
                $newTo = bcadd($to['balance'], $amount, 4);

                $conn->update('accounts', ['balance' => $newFrom], ['id' => $fromAccountId]);
                $conn->update('accounts', ['balance' => $newTo], ['id' => $toAccountId]);

                $uuid = Uuid::uuid4()->toString();
                $conn->insert('transactions', [
                    'uuid' => $uuid,
                    'from_account_id' => $fromAccountId,
                    'to_account_id' => $toAccountId,
                    'amount' => $amount,
                    'currency' => strtoupper($currency),
                    'status' => 'completed',
                    'idempotency_key' => $idempotencyKey,
                ]);

                return ['uuid' => $uuid, 'status' => 'completed'];
            });

            $this->logger->info('Transfer successful', ['from' => $fromAccountId, 'to' => $toAccountId, 'amount' => $amount, 'uuid' => $result['uuid']]);
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Transfer failed', ['ex' => $e->getMessage(), 'from' => $fromAccountId, 'to' => $toAccountId, 'amount' => $amount]);
            // Also persist a failed transfer row for audit (optional)
            try {
                $uuid = Uuid::uuid4()->toString();
                $this->em->getConnection()->insert('transactions', [
                    'uuid' => $uuid,
                    'from_account_id' => $fromAccountId,
                    'to_account_id' => $toAccountId,
                    'amount' => $amount,
                    'currency' => strtoupper($currency),
                    'status' => 'failed',
                    'idempotency_key' => $idempotencyKey,
                ]);
            } catch (\Throwable) {
                // ignore second-level failures
            }
            throw $e;
        } finally {
            if ($acquired) {
                try { $this->redis->del($lockKey); } catch (\Throwable) {}
            }
        }
    }
}
