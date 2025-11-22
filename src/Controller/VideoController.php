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
            FROM videos v 
            LEFT JOIN user u ON v.user_id = u.id 
            ORDER BY v.created_at DESC
        ');

        return new JsonResponse([
            'success' => true,
            'data' => $videos,
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
            FROM videos v 
            LEFT JOIN user u ON v.user_id = u.id 
            WHERE v.id = ?
        ', [$id]);

        if (!$video) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Video not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        $connection->executeStatement('
            UPDATE videos SET views_count = views_count + 1 WHERE id = ?
        ', [$id]);

        $video['views_count'] += 1;

        return new JsonResponse([
            'success' => true,
            'data' => $video,
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

    $title = $request->request->get('title');
    $description = $request->request->get('description');
    $videoFile = $request->files->get('video');
    $thumbnailFile = $request->files->get('thumbnail');

    if (!$title || !$videoFile) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Title and video file is required.'
        ], Response::HTTP_BAD_REQUEST);
    }

    // Upload folders (make sure they exist)
    $videoDir = $this->getParameter('kernel.project_dir') . '/public/uploads/videos/';
    $thumbnailDir = $this->getParameter('kernel.project_dir') . '/public/uploads/thumbnails/';

    try {
        // Move Video File
        $videoName = uniqid() . '.' . $videoFile->guessExtension();
        $videoFile->move($videoDir, $videoName);

        // Move Thumbnail (optional)
        $thumbnailName = null;
        if ($thumbnailFile) {
            $thumbnailName = uniqid() . '.' . $thumbnailFile->guessExtension();
            $thumbnailFile->move($thumbnailDir, $thumbnailName);
        }

        $connection = $this->entityManager->getConnection();
        $connection->insert('videos', [
            'title' => $title,
            'description' => $description,
            'file_path' => '/uploads/videos/' . $videoName,
            'thumbnail_path' => $thumbnailName ? '/uploads/thumbnails/' . $thumbnailName : null,
            'duration' => 120,
            'views_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'user_id' => $user->getId()
        ]);

        return new JsonResponse([
            'success' => true,
            'message' => 'Video uploaded successfully.'
        ], Response::HTTP_CREATED);

    } catch (\Exception $e) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Upload failed: ' . $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

#[Route('/{id}', name: 'video_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $connection = $this->entityManager->getConnection();
        $video = $connection->fetchAssociative('SELECT * FROM videos WHERE id = ?', [$id]);

        if (!$video) {
            return new JsonResponse(['success' => false, 'message' => 'Video not found.'], 404);
        }

        if ($video['user_id'] !== $user->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'Not authorized to update this video.'], 403);
        }

        $title = $request->request->get('title', $video['title']);
        $description = $request->request->get('description', $video['description']);

        $connection->update('videos', [
            'title' => $title,
            'description' => $description,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        return new JsonResponse(['success' => true, 'message' => 'Video updated successfully.']);
    }

    #[Route('/{id}', name: 'video_delete', methods: ['DELETE'])]
public function delete(int $id): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Authentication required.'
        ], Response::HTTP_UNAUTHORIZED);
    }

    $connection = $this->entityManager->getConnection();

    // Check if video exists
    $video = $connection->fetchAssociative('SELECT * FROM videos WHERE id = ?', [$id]);
    if (!$video) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Video not found.'
        ], Response::HTTP_NOT_FOUND);
    }

    // Check ownership
    if ($video['user_id'] !== $user->getId()) {
        return new JsonResponse([
            'success' => false,
            'message' => 'You do not have permission to delete this video.'
        ], Response::HTTP_FORBIDDEN);
    }

    // File folders
    $projectDir = $this->getParameter('kernel.project_dir');
    $videoPath = $projectDir . '/public' . $video['file_path'];
    $thumbnailPath = $projectDir . '/public' . $video['thumbnail_path'];

    // Remove video file
    if (file_exists($videoPath)) {
        @unlink($videoPath);
    }

    // Remove thumbnail
    if ($video['thumbnail_path'] && file_exists($thumbnailPath)) {
        @unlink($thumbnailPath);
    }

    // Delete from DB
    $connection->delete('videos', ['id' => $id]);

    return new JsonResponse([
        'success' => true,
        'message' => 'Video deleted successfully.'
    ]);
}

}


