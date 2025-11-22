<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/posts')]
class CommentController extends AbstractController
{
    #[Route('/{postId<\d+>}/comments', name: 'post_comments', methods: ['POST'])]
    public function addComment(int $postId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $post = $em->getRepository(Post::class)->find($postId);

        if (!$post) {
            return $this->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['content'])) {
            return $this->json(['success' => false, 'message' => 'Content is required'], 400);
        }

        $comment = new Comment();
        $comment->setUser($user);
        $comment->setPost($post);
        $comment->setContent($data['content']);
        $comment->setCreatedAt(new \DateTime());
        $comment->setUpdatedAt(new \DateTime());

        $post->setCommentsCount($post->getCommentsCount() + 1);

        $em->persist($comment);
        $em->persist($post);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'comment_id' => $comment->getId(),
            'content' => $comment->getContent(),
            'commentsCount' => $post->getCommentsCount()
        ], 201);
    }

    #[Route('/{postId<\d+>}/comments', name: 'get_post_comments', methods: ['GET'])]
    public function getComments(int $postId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $post = $em->getRepository(Post::class)->find($postId);

        if (!$post) {
            return $this->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        $limit = $request->query->get('limit', 20);
        $offset = $request->query->get('offset', 0);

        $comments = $em->getRepository(Comment::class)->findCommentsByPost($post, $limit, $offset);

        $commentsData = [];
        foreach ($comments as $comment) {
            $commentsData[] = [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'likesCount' => $comment->getLikesCount(),
                'user' => [
                    'id' => $comment->getUser()->getId(),
                    'email' => $comment->getUser()->getEmail(),
                    'avatar' => $comment->getUser()->getAvatarPath()
                ],
                'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $comment->getUpdatedAt()->format('Y-m-d H:i:s')
            ];
        }

        return $this->json([
            'success' => true,
            'count' => count($commentsData),
            'comments' => $commentsData
        ]);
    }

    #[Route('/comments/{commentId<\d+>}', name: 'update_comment', methods: ['PUT'])]
    public function updateComment(int $commentId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $comment = $em->getRepository(Comment::class)->find($commentId);

        if (!$comment) {
            return $this->json(['success' => false, 'message' => 'Comment not found'], 404);
        }

        if ($comment->getUser()->getId() !== $user->getId()) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!empty($data['content'])) {
            $comment->setContent($data['content']);
            $comment->setUpdatedAt(new \DateTime());
            $em->flush();
        }

        return $this->json([
            'success' => true,
            'message' => 'Comment updated successfully'
        ]);
    }

    #[Route('/comments/{commentId<\d+>}', name: 'delete_comment', methods: ['DELETE'])]
    public function deleteComment(int $commentId, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $comment = $em->getRepository(Comment::class)->find($commentId);

        if (!$comment) {
            return $this->json(['success' => false, 'message' => 'Comment not found'], 404);
        }

        if ($comment->getUser()->getId() !== $user->getId()) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $post = $comment->getPost();
        $post->setCommentsCount($post->getCommentsCount() - 1);

        $em->remove($comment);
        $em->persist($post);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }
}