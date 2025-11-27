<?php
namespace App\Controller\Api;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use App\Repository\AccountRepository;
use App\Service\TransactionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/transactions')]
class TransactionController extends AbstractController
{
    private TransactionService $transactionService;
    private ValidatorInterface $validator;
    private EntityManagerInterface $em;

    public function __construct(TransactionService $transactionService, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        $this->transactionService = $transactionService;
        $this->validator = $validator;
        $this->em = $em;
    }

    // POST /api/transactions
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?: [];
        $idempotencyKey = $request->headers->get('Idempotency-Key');

        $constraints = new Assert\Collection([
            'from_account_id' => [
                new Assert\NotBlank(),
                new Assert\Positive()
            ],
            'to_account_id' => [
                new Assert\NotBlank(),
                new Assert\Positive()
            ],
            'amount' => [
                new Assert\NotBlank(),
                new Assert\Regex('/^\d+(\.\d{1,4})?$/')
            ],
            'currency' => [
                new Assert\NotBlank(),
                new Assert\Length(3)
            ],
        ]);

        $violations = $this->validator->validate($payload, $constraints);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = $v->getPropertyPath() . ': ' . $v->getMessage();
            }
            return $this->json(['errors' => $errors], 400);
        }

        // Auth: ensure user can debit from from_account (this logic can depend on your security)
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        // Ensure fromAccount belongs to current user
        $fromAccount = $this->em->getRepository(\App\Entity\Account::class)
            ->findOneBy(['id' => (int)$payload['from_account_id'], 'user' => $user]);

        if (!$fromAccount) {
            return $this->json(['error' => 'You can only transfer from your own account'], 403);
        }

        // call service
        try {
            $res = $this->transactionService->transfer(
                (int)$payload['from_account_id'],
                (int)$payload['to_account_id'],
                (string)$payload['amount'],
                strtoupper($payload['currency']),
                $idempotencyKey
            );

            return $this->json($res, 201);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'internal_error', 'message' => $e->getMessage()], 500);
        }
    }

    // GET /api/transactions
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(TransactionRepository $repo): JsonResponse
    {
        $transactions = $repo->findAll();
        $data = array_map(function(Transaction $t) {
            return [
                'id' => $t->getId(),
                'uuid' => $t->getUuid(),
                'type' => $t->getType(),
                'amount' => $t->getAmount(),
                'created_at' => $t->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $transactions);

        return $this->json($data);
    }
}
