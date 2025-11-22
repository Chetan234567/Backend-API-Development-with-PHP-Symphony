<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProfileController extends AbstractController
{
    #[Route('/api/profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();
        return new JsonResponse([
            'email' => $user->getEmail(),
            'username' => $user->getUsername()
        ]);
    }

     #[Route('/api/profile', name: 'delete_profile', methods: ['DELETE'])]
    public function deleteProfile(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentication required.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Account deleted successfully. Token is no longer valid.'
        ], Response::HTTP_OK);
    }
}
