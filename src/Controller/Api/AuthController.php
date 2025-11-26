<?php
namespace App\Controller\Api;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserProviderInterface $userProvider,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return new JsonResponse(['error' => 'Invalid JSON body'], 400);
        }

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password required'], 400);
        }

        try {
            $user = $userProvider->loadUserByIdentifier($email);
        } catch (UserNotFoundException $e) {
            // return JSON 401 instead of letting exception produce an HTML error page
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        } catch (\Throwable $e) {
            // fallback: don't expose internal errors, return generic auth failure
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        if (!$user instanceof UserInterface || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        try {
            $token = $jwtManager->create($user);
        } catch (\Throwable $e) {
            // use injected logger
            $this->logger->error('JWT creation failed: '.$e->getMessage(), ['exception' => $e]);

            if ($this->getParameter('kernel.debug')) {
                return new JsonResponse(['error' => 'Unable to create token', 'detail' => $e->getMessage()], 500);
            }

            return new JsonResponse(['error' => 'Unable to create token'], 500);
        }

        return new JsonResponse([
            'token' => $token
        ]);
    }
}
