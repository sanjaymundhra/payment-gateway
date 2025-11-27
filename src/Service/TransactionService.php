<?php
namespace App\Service;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Entity\IdempotencyKey;
use App\Entity\FailedTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Predis\Client;

class TransactionService
{
    private Client $redis;
    private EntityManagerInterface $em;
    private int $scale = 4;

    public function __construct(Client $redis,EntityManagerInterface $em)
    {
        $this->redis = $redis;
        $this->em = $em;
    }

    /**
     * Perform a transfer: debit fromAccount, credit toAccount.
     *
     * Returns ['status' => 'ok', 'uuid' => '<uuid>'] on success.
     * Throws RuntimeException on business rules violation.
     */
    public function transfer(
        int $fromAccountId,
        int $toAccountId,
        string $amount,
        string $currency,
        ?string $idempotencyKey = null
    ): array {
        if (bccomp($amount, '0', $this->scale) <= 0) {
            throw new \RuntimeException('amount_must_be_positive');
        }
        if ($fromAccountId === $toAccountId) {
            throw new \RuntimeException('cannot_transfer_to_same_account');
        }

        if ($idempotencyKey) {
            $repo = $this->em->getRepository(IdempotencyKey::class);
            if ($existing = $repo->find($idempotencyKey)) {
                return json_decode($existing->getResponse(), true);
            }
        }

        $lockKey = "transfer_lock:{$fromAccountId}:{$toAccountId}";
        if (!$this->redis->set($lockKey, "1", 'NX', 'EX', 5)) {
            throw new \RuntimeException('transfer_in_progress');
        }

        try {
            $result = $this->em->transactional(function(EntityManagerInterface $em) 
                use ($fromAccountId, $toAccountId, $amount, $currency, $idempotencyKey) 
            {
                $from = $em->find(Account::class, $fromAccountId, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
                $to   = $em->find(Account::class, $toAccountId,   \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);

                if (!$from || !$to) {
                    throw new \RuntimeException('account_not_found');
                }

                if (
                    $from->getCurrency() !== $currency ||
                    $to->getCurrency() !== $currency
                ) {
                    throw new \RuntimeException('currency_mismatch');
                }

                if (bccomp($from->getBalance(), $amount, $this->scale) < 0) {
                    throw new \RuntimeException('insufficient_balance');
                }

                $from->setBalance(bcsub($from->getBalance(), $amount, $this->scale));
                $to->setBalance(bcadd($to->getBalance(), $amount, $this->scale));

                $debit  = new Transaction($from, Transaction::TYPE_DEBIT,  $amount, $currency);
                $credit = new Transaction($to,   Transaction::TYPE_CREDIT, $amount, $currency);

                $em->persist($debit);
                $em->persist($credit);

                $response = [
                    'status' => 'ok',
                    'uuid'   => $debit->getUuid(),
                    'amount' => $amount,
                    'currency' => $currency
                ];

                if ($idempotencyKey) {
                    $ik = new IdempotencyKey($idempotencyKey, json_encode($response));
                    $em->persist($ik);
                }

                return $response;
            });

            return $result;

        } catch (\Throwable $e) {

            $failed = new FailedTransaction(
                $fromAccountId,
                $toAccountId,
                $amount,
                $e->getMessage()
            );
            $this->em->persist($failed);
            $this->em->flush();

            throw $e;

        } finally {
            $this->redis->del($lockKey);
        }
    }

}
