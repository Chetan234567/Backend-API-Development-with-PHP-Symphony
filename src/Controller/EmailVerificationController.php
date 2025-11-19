<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailVerificationController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/email/verify/{token}', name: 'email_verify', methods: ['GET'])]
    public function verify(string $token): Response
    {
        // Debug: Log the token being searched for
        error_log("Searching for token: " . $token);
        
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'verificationToken' => $token
        ]);
        
        // Debug: Check what we found
        if ($user) {
            error_log("User found: " . $user->getEmail());
        } else {
            error_log("No user found with token: " . $token);
            
            // Let's check if there are any users with verification tokens at all
            $allUsersWithTokens = $this->entityManager->getRepository(User::class)
                ->findBy(['verificationToken' => null], null, 10);
            error_log("Users with non-null tokens: " . count($allUsersWithTokens));
        }
        
        if (!$user) {
            return new JsonResponse(
                ['error' => 'Invalid verification token'], 
                Response::HTTP_NOT_FOUND
            );
        }
        
        // Mark user as verified
        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'message' => 'Email verified successfully! You can now login.'
        ]);
    }
}