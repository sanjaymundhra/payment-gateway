<?php
namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\TransactionService;
use App\Entity\Account;
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/transactions', name: 'api_transactions_')]
class TransactionController extends AbstractController
{
    private TransactionService $transactionService;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;

    public function __construct(
        TransactionService $transactionService,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ) {
        $this->transactionService = $transactionService;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    #[Route('/transactions', name: 'api_transactions_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?: [];
        $idempotencyKey = $request->headers->get('Idempotency-Key');

        $constraints = new Assert\Collection([
            'from_account_id' => [new Assert\NotBlank(), new Assert\Type('digit')],
            'to_account_id' => [new Assert\NotBlank(), new Assert\Type('digit')],
            'amount' => [new Assert\NotBlank(), new Assert\Regex('/^\d+(\.\d{1,4})?$/')],
            'currency' => [new Assert\NotBlank(), new Assert\Length(3)],
        ]);

        $violations = $this->validator->validate($payload, $constraints);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = $v->getPropertyPath() . ': ' . $v->getMessage();
            }
            return $this->json(['errors' => $errors], 400);
        }

        $user = $this->getUser();

        $fromAccount = $this->entityManager
            ->getRepository(Account::class)
            ->findOneBy([
                'id' => $payload['from_account_id'],
                'user' => $user
            ]);

        if (!$fromAccount) {
            return $this->json([
                'error' => 'You can only transfer from your own account'
            ], 403);
        }

        $toAccount = $this->entityManager
            ->getRepository(Account::class)
            ->find($payload['to_account_id']);

        if (!$toAccount) {
            return $this->json(['error' => 'Destination account not found'], 404);
        }

        try {
            $result = $this->transactionService->transfer(
                $fromAccount->getId(),
                $toAccount->getId(),
                (string)$payload['amount'],
                (string)$payload['currency'],
                $idempotencyKey
            );

            return $this->json(['status' => $result['status'], 'uuid' => $result['uuid']], 201);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'internal_error'], 500);
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(TransactionRepository $repo): JsonResponse
    {
        $transactions = $repo->findAll();
        $data = array_map(fn(Transaction $t) => [
            'id' => $t->getId(),
            'account_id' => $t->getFromAccount()->getId(),
            'amount' => $t->getAmount(),
            'status' => $t->getStatus(),
            'created_at' => $t->getCreatedAt()->format('Y-m-d H:i:s')
        ], $transactions);

        return $this->json($data);
    }

    #[Route('/api/transfer', name: 'api_transfer', methods: ['POST'])]
    public function transfer(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        // Get the logged-in user from the token
        $user = $this->getUser(); // Symfony automatically injects the user via token
        
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);

        $toAccountId = $data['to_account_id'] ?? null;
        $amount = $data['amount'] ?? null;
        $currency = $data['currency'] ?? null;

        if (!$toAccountId || !$amount || !$currency) {
            return $this->json(['error' => 'Missing parameters'], 400);
        }

        // Find sender and receiver accounts
        $fromAccount = $em->getRepository(Account::class)->findOneBy(['user' => $user]);
        $toAccount = $em->getRepository(Account::class)->find($toAccountId);

        if (!$fromAccount || !$toAccount) {
            return $this->json(['error' => 'Invalid account(s)'], 404);
        }

        if ($fromAccount->getBalance() < $amount) {
            return $this->json(['error' => 'Insufficient balance'], 400);
        }

        // Create transaction
        $transaction = new Transaction();
        $transaction->setFromAccount($fromAccount);
        $transaction->setToAccount($toAccount);
        $transaction->setAmount($amount);
        $transaction->setCurrency($currency);
        $transaction->setStatus('completed');
        $transaction->setCreatedAt(new \DateTimeImmutable());
        $transaction->setUpdatedAt(new \DateTimeImmutable());
        $transaction->setUuid(uniqid());

        // Update balances
        $fromAccount->setBalance($fromAccount->getBalance() - $amount);
        $toAccount->setBalance($toAccount->getBalance() + $amount);

        $em->persist($transaction);
        $em->flush();

        return $this->json([
            'message' => 'Transfer successful',
            'transaction_id' => $transaction->getId(),
            'from_account_balance' => $fromAccount->getBalance(),
        ]);
    }



}
