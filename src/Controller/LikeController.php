<?php

namespace App\Controller;

use App\Entity\Like;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/posts')]
class LikeController extends AbstractController
{
    #[Route('/{postId<\d+>}/like', name: 'post_like', methods: ['POST'])]
    public function likePost(int $postId, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $post = $em->getRepository(Post::class)->find($postId);
        if (!$post) {
            return $this->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        $likeRepository = $em->getRepository(Like::class);

        if ($likeRepository->isLiked($user, $post)) {
            return $this->json(['success' => false, 'message' => 'Already liked'], 400);
        }

        $like = new Like();
        $like->setUser($user);
        $like->setPost($post);
        $like->setCreatedAt(new \DateTimeImmutable());

        $post->setLikesCount($post->getLikesCount() + 1);

        $em->persist($like);
        $em->persist($post);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Post liked successfully',
            'likesCount' => $post->getLikesCount()
        ]);
    }

    #[Route('/{postId<\d+>}/unlike', name: 'post_unlike', methods: ['DELETE'])]
    public function unlikePost(int $postId, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $post = $em->getRepository(Post::class)->find($postId);
        if (!$post) {
            return $this->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        $likeRepository = $em->getRepository(Like::class);
        $like = $likeRepository->findOneBy(['user' => $user, 'post' => $post]);

        if (!$like) {
            return $this->json(['success' => false, 'message' => 'Not liked yet'], 400);
        }

        $post->setLikesCount(max(0, $post->getLikesCount() - 1));

        $em->remove($like);
        $em->persist($post);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Post unliked successfully',
            'likesCount' => $post->getLikesCount()
        ]);
    }

    #[Route('/{postId<\d+>}/likes', name: 'post_likes', methods: ['GET'])]
    public function getPostLikes(int $postId, EntityManagerInterface $em): JsonResponse
    {
        $post = $em->getRepository(Post::class)->find($postId);
        if (!$post) {
            return $this->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        $likes = $em->getRepository(Like::class)->findLikesByPost($post);

        $likesData = [];
        foreach ($likes as $like) {
            $likesData[] = [
                'user_id' => $like->getUser()->getId(),
                'email' => $like->getUser()->getEmail(),
                'avatar' => $like->getUser()->getAvatarPath(),
                'likedAt' => $like->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        return $this->json([
            'success' => true,
            'count' => count($likesData),
            'likes' => $likesData
        ]);
    }
}
