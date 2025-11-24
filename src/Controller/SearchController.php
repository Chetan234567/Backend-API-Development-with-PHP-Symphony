<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/search')]
class SearchController extends AbstractController
{
    #[Route('/users', name: 'search_users', methods: ['GET'])]
    public function searchUsers(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        $limit = (int)$request->query->get('limit', 20);

        if (strlen($query) < 2) {
            return $this->json(['success' => false, 'message' => 'Query too short'], 400);
        }

        $users = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.email LIKE :query OR u.username LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'avatar' => $user->getAvatarPath()
                    ? '/uploads/profile/' . $user->getAvatarPath()
                    : null
            ];
        }

        return $this->json([
            'success' => true,
            'count' => count($data),
            'users' => $data
        ]);
    }

    #[Route('/posts', name: 'search_posts', methods: ['GET'])]
    public function searchPosts(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        $limit = (int)$request->query->get('limit', 20);
        $offset = (int)$request->query->get('offset', 0);

        if (strlen($query) < 2) {
            return $this->json(['success' => false, 'message' => 'Query too short'], 400);
        }

        $posts = $em->getRepository(Post::class)->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->where('p.content LIKE :query OR u.username LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($posts as $post) {
            $data[] = [
                'id' => $post->getId(),
                'content' => $post->getContent(),
                'imageUrl' => $post->getImageUrl(),
                'likesCount' => $post->getLikesCount(),
                'commentsCount' => $post->getCommentsCount(),
                'sharesCount' => $post->getSharesCount(),
                'createdAt' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
                'user' => [
                    'id' => $post->getUser()->getId(),
                    'username' => $post->getUser()->getUsername(),
                    'avatar' => $post->getUser()->getAvatarPath()
                        ? '/uploads/profile/' . $post->getUser()->getAvatarPath()
                        : null
                ]
            ];
        }

        return $this->json([
            'success' => true,
            'count' => count($data),
            'posts' => $data
        ]);
    }

    #[Route('', name: 'search_all', methods: ['GET'])]
    public function searchAll(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        $limit = (int)$request->query->get('limit', 10);

        if (strlen($query) < 2) {
            return $this->json(['success' => false, 'message' => 'Query too short'], 400);
        }

        // Search Users
        $users = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.email LIKE :query OR u.username LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Search Posts
        $posts = $em->getRepository(Post::class)->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->where('p.content LIKE :query OR u.username LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults($limit)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $results = [];

        foreach ($users as $user) {
            $results[] = [
                'type' => 'user',
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'avatar' => $user->getAvatarPath()
                    ? '/uploads/profile/' . $user->getAvatarPath()
                    : null
            ];
        }

        foreach ($posts as $post) {
            $results[] = [
                'type' => 'post',
                'id' => $post->getId(),
                'content' => $post->getContent(),
                'likesCount' => $post->getLikesCount(),
                'user' => [
                    'id' => $post->getUser()->getId(),
                    'username' => $post->getUser()->getUsername()
                ]
            ];
        }

        return $this->json([
            'success' => true,
            'count' => count($results),
            'results' => $results
        ]);
    }
}
