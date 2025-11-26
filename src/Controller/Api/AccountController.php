<?php

namespace App\Controller\Api;

use App\Repository\AccountRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Account;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/accounts', name: 'api_accounts_')]
class AccountController extends AbstractController
{
    private $em;
    private $userRepo;
    private $accountRepo;

    public function __construct(EntityManagerInterface $em, UserRepository $userRepo, AccountRepository $accountRepo)
    {
        $this->em = $em;
        $this->userRepo = $userRepo;
        $this->accountRepo = $accountRepo;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(AccountRepository $repo): JsonResponse
    {
        $accounts = $repo->findAll();
        $data = array_map(fn($acc) => [
            'id' => $acc->getId(),
            'account_holder_id' => $acc->getUser()->getId(),
            'balance' => $acc->getBalance(),
            'currency' => $acc->getCurrency(),
            'created_at' => $acc->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $accounts);

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['user_id'] ?? null;
        $currency = $data['currency'] ?? 'INR';
        $balance = $data['balance'] ?? '0.00';

        $user = $this->userRepo->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $account = new Account($user, $currency);
        $account->setBalance($balance);

        $this->em->persist($account);
        $this->em->flush();

        return $this->json(['id' => $account->getId(), 'user_id' => $userId, 'balance' => $balance]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show($id, AccountRepository $repo): JsonResponse
    {
        $acc = $repo->find($id);
        if (!$acc) {
            return $this->json(['error' => 'Account not found'], 404);
        }

        return $this->json([
            'id' => $acc->getId(),
            'account_holder_id' => $acc->getUser()->getId(),
            'balance' => $acc->getBalance(),
            'currency' => $acc->getCurrency(),
            'created_at' => $acc->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
}
