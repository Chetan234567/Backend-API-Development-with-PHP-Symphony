<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;


class ProfileController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    #[Route('/api/profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'displayName' => $user->getDisplayName(),
                'bio' => $user->getBio(),
                'profilePicture' => $user->getProfilePicture() 
                    ? '/uploads/profile/' . $user->getProfilePicture()
                    : null,
            ]
        ]);
    }

    #[Route('/api/profile/upload', name: 'upload_profile_picture', methods: ['POST'])]
    public function uploadProfilePicture(
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $file = $request->files->get('profilePicture');

        if (!$file) {
            return $this->json(['message' => 'No image file uploaded'], 400);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return $this->json(['message' => 'Only JPG, JPEG, PNG allowed'], 400);
        }

        $fileName = uniqid('avatar_') . '.' . $file->guessExtension();
        $uploadDir = $this->getParameter('profile_picture_dir');
        $file->move($uploadDir, $fileName);

        // Delete old picture if exists
        if ($user->getProfilePicture()) {
            $oldPath = $uploadDir . '/' . $user->getProfilePicture();
            if (file_exists($oldPath)) unlink($oldPath);
        }

        $user->setProfilePicture($fileName);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Profile picture uploaded',
            'profilePictureUrl' => '/uploads/profile/' . $fileName
        ]);
    }

    
    #[Route('/api/profile', name: 'delete_profile', methods: ['DELETE'])]
    public function deleteProfile(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Authentication required.'
            ], 401);
        }

        // Delete profile picture before account delete
        if ($user->getProfilePicture()) {
            $path = $this->getParameter('profile_picture_dir') . '/' . $user->getProfilePicture();
            if (file_exists($path)) unlink($path);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }
}
