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
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/transaction', name: 'api_transaction')]
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
}
