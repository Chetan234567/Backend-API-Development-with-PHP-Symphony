<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\TokenBlacklist;
use Doctrine\ORM\EntityManagerInterface;

class UserLogoutController 
{
    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader) {
            return new JsonResponse(['error' => 'Token not provided'], 400);
        }

        $jwt = str_replace('Bearer ', '', $authHeader);

        // Save token in database blacklist
        $blacklist = new TokenBlacklist();
        $blacklist->setToken($jwt);
        $blacklist->setCreatedAt(new \DateTimeImmutable());

        $em->persist($blacklist);
        $em->flush();

        return new JsonResponse(['message' => 'Logged out successfully']);
    }
}
