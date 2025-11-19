<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // This method is never executed.
        // Symfony handles login automatically using JWT (LexikJWTAuthenticationBundle).
        return new JsonResponse([
            'message' => 'You should not see this message.'
        ], 400);
    }
}
