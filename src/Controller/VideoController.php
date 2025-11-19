<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/videos')]
class VideoController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'video_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
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
            'message' => 'Videos retrieved successfully.'
        ]);
    }

    #[Route('/{id}', name: 'video_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentication required.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $connection = $this->entityManager->getConnection();
        $video = $connection->fetchAssociative('
            SELECT v.*, u.email as user_email 
            FROM video v 
            LEFT JOIN user u ON v.user_id = u.id 
            WHERE v.id = ?
        ', [$id]);

        if (!$video) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Video not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Increment view count
        $connection->executeStatement('
            UPDATE video SET views_count = views_count + 1 WHERE id = ?
        ', [$id]);

        $formattedVideo = [
            'id' => $video['id'],
            'title' => $video['title'],
            'description' => $video['description'],
            'duration' => $video['duration'],
            'viewsCount' => $video['views_count'] + 1,
            'thumbnailPath' => $video['thumbnail_path'],
            'filePath' => $video['file_path'],
            'createdAt' => $video['created_at'],
            'user' => [
                'id' => $video['user_id'],
                'email' => $video['user_email']
            ]
        ];

        return new JsonResponse([
            'success' => true,
            'data' => $formattedVideo,
            'message' => 'Video retrieved successfully.'
        ]);
    }

    #[Route('/upload', name: 'video_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentication required.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        
        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;

        if (!$title) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Video title is required.'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $connection = $this->entityManager->getConnection();
            
            $connection->insert('video', [
                'title' => $title,
                'description' => $description,
                'file_path' => '/uploads/videos/sample.mp4',
                'thumbnail_path' => '/uploads/thumbnails/sample.jpg',
                'duration' => 120,
                'views_count' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id' => $user->getId()
            ]);

            $videoId = $connection->lastInsertId();

            // Get the created video
            $video = $connection->fetchAssociative('
                SELECT v.*, u.email as user_email 
                FROM video v 
                LEFT JOIN user u ON v.user_id = u.id 
                WHERE v.id = ?
            ', [$videoId]);

            $formattedVideo = [
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

            return new JsonResponse([
                'success' => true,
                'data' => $formattedVideo,
                'message' => 'Video created successfully.'
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Video creation failed: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}