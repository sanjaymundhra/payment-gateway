<?php
namespace App\Controller\Api;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;

class AuthController extends AbstractController
{
    private JWTTokenManagerInterface $jwtManager;
    private UserPasswordHasherInterface $passwordHasher;
    private TokenExtractorInterface $tokenExtractor;
    private JWTEncoderInterface $jwtEncoder;
    private LoggerInterface $logger;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher,
        TokenExtractorInterface $tokenExtractor,
        JWTEncoderInterface $jwtEncoder,
        LoggerInterface $logger
    ) {
        $this->jwtManager = $jwtManager;
        $this->passwordHasher = $passwordHasher;
        $this->tokenExtractor = $tokenExtractor;
        $this->jwtEncoder = $jwtEncoder;
        $this->logger = $logger;
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request, UserProviderInterface $userProvider): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON body'], 400);
        }

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password required'], 400);
        }

        // --- Already Logged In? ----------------------------------------------------
        $existingToken = $this->tokenExtractor->extract($request);

        if ($existingToken) {
            try {
                $payload = $this->jwtEncoder->decode($existingToken);

                if ($payload && ($payload['username'] ?? null) === $email) {
                    return new JsonResponse([
                        'token' => $existingToken,
                        'message' => 'Already logged in'
                    ]);
                }
            } catch (\Throwable $e) {
                // ignore invalid token and continue
            }
        }

        // --- Validate Credentials ---------------------------------------------------
        try {
            $user = $userProvider->loadUserByIdentifier($email);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        if (!$user instanceof UserInterface || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        // --- Generate New Token -----------------------------------------------------
        try {
            $token = $this->jwtManager->create($user);
        } catch (\Throwable $e) {
            $this->logger->error('JWT creation failed: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Unable to create token'], 500);
        }

        return new JsonResponse(['token' => $token]);
    }
}
