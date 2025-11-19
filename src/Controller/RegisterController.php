<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegisterController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Email & password required'], 400);
        }

        // Check if user already exists
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'User already exists'], 400);
        }

        // Create new user
        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername($data['email']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);
        $user->setCreatedAt(new \DateTimeImmutable());
        $token = bin2hex(random_bytes(32));
        $user->setVerificationToken($token);       
        $user->setIsVerified(false);

        $em->persist($user);
        $em->flush();

// Build verification link (adjust host/port as needed)
$verificationUrl = sprintf('http://localhost:8000/api/email/verify/%s', ed9d858ee4fcb90e9389b4544a496a9b0304029eee1aea860532f11fe54032a6, $user->getVerificationToken()
);
$messageBody = "Click to verify your email: " . $verificationUrl;

        return new JsonResponse(['message' => 'User registered successfully'], 201);
    }
}
