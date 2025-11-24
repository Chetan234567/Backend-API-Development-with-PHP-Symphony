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

    // --- HOME FEED (Followed Users Only + Cursor Pagination) ---
    #[Route('', name: 'feed_home', methods: ['GET'])]
    public function home(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Authentication required'], 401);
        }

        $cursor = $request->query->getInt('cursor', PHP_INT_MAX);
        $limit = $request->query->getInt('limit', 10);

        $connection = $this->entityManager->getConnection();

        $sql = '
            SELECT v.*, u.email as user_email, u.username, u.profile_picture
            FROM videos v
            INNER JOIN follow f ON f.following_id = v.user_id
            INNER JOIN user u ON v.user_id = u.id
            WHERE f.follower_id = :uid AND v.id < :cursor
            ORDER BY v.id DESC
            LIMIT :limit
        ';

        $stmt = $connection->prepare($sql);
        $stmt->bindValue('uid', $user->getId(), \PDO::PARAM_INT);
        $stmt->bindValue('cursor', $cursor, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);

        $videos = $stmt->executeQuery()->fetchAllAssociative();

        return $this->json([
            'success' => true,
            'nextCursor' => count($videos) ? end($videos)['id'] : null,
            'data' => $this->formatVideos($videos),
            'message' => 'Home feed loaded successfully.'
        ]);
    }

    // --- TRENDING FEED ---
    #[Route('/trending', name: 'feed_trending', methods: ['GET'])]
    public function trending(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 10);

        $connection = $this->entityManager->getConnection();

        $sql = '
            SELECT v.*, u.email as user_email, u.username, u.profile_picture
            FROM videos v
            INNER JOIN user u ON v.user_id = u.id
            ORDER BY (v.views_count * 0.6 + v.likes_count * 0.4) DESC
            LIMIT :limit
        ';

        $stmt = $connection->prepare($sql);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);

        $videos = $stmt->executeQuery()->fetchAllAssociative();

        return $this->json([
            'success' => true,
            'data' => $this->formatVideos($videos),
            'message' => 'Trending feed loaded successfully.'
        ]);
    }

    private function formatVideos(array $videos): array
    {
        $output = [];
        foreach ($videos as $v) {
            $output[] = [
                'id' => $v['id'],
                'title' => $v['title'],
                'description' => $v['description'],
                'duration' => $v['duration'],
                'viewsCount' => $v['views_count'],
                'likesCount' => $v['likes_count'] ?? 0,
                'thumbnailUrl' => '/uploads/videos/' . $v['thumbnail_path'],
                'videoUrl' => '/uploads/videos/' . $v['file_path'],
                'createdAt' => $v['created_at'],
                'creator' => [
                    'id' => $v['user_id'],
                    'email' => $v['user_email'],
                    'username' => $v['username'],
                    'profilePicture' => $v['profile_picture']
                        ? '/uploads/profile/' . $v['profile_picture']
                        : null,
                ],
            ];
        }
        return $output;
    }
}
