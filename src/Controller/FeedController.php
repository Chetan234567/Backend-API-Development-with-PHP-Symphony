<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/feed')]
class FeedController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'feed_home', methods: ['GET'])]
    public function home(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentication required.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $connection = $this->entityManager->getConnection();
        $videos = $connection->fetchAllAssociative('
            SELECT v.*, u.email as user_email 
            FROM video v 
            LEFT JOIN user u ON v.user_id = u.id 
            ORDER BY v.created_at DESC
            LIMIT 10
        ');

        $formattedVideos = [];
        foreach ($videos as $video) {
            $formattedVideos[] = [
                'id' => $video['id'],
                'title' => $video['title'],
                'description' => $video['description'],
                'duration' => $video['duration'],
                'viewsCount' => $video['views_count'],
                'thumbnailPath' => $video['thumbnail_path'],
                'filePath' => $video['file_path'],
                'createdAt' => $video['created_at'],
                'user' => [
                    'id' => $video['user_id'],
                    'email' => $video['user_email']
                ]
            ];
        }

        return new JsonResponse([
            'success' => true,
            'data' => $formattedVideos,
            'message' => 'Home feed loaded successfully.'
        ]);
    }

    #[Route('/trending', name: 'feed_trending', methods: ['GET'])]
    public function trending(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentication required.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $connection = $this->entityManager->getConnection();
        $videos = $connection->fetchAllAssociative('
            SELECT v.*, u.email as user_email 
            FROM video v 
            LEFT JOIN user u ON v.user_id = u.id 
            ORDER BY v.views_count DESC, v.created_at DESC
            LIMIT 10
        ');

        $formattedVideos = [];
        foreach ($videos as $video) {
            $formattedVideos[] = [
                'id' => $video['id'],
                'title' => $video['title'],
                'description' => $video['description'],
                'duration' => $video['duration'],
                'viewsCount' => $video['views_count'],
                'thumbnailPath' => $video['thumbnail_path'],
                'filePath' => $video['file_path'],
                'createdAt' => $video['created_at'],
                'user' => [
                    'id' => $video['user_id'],
                    'email' => $video['user_email']
                ]
            ];
        }

        return new JsonResponse([
            'success' => true,
            'data' => $formattedVideos,
            'message' => 'Trending feed loaded successfully.'
        ]);
    }
}