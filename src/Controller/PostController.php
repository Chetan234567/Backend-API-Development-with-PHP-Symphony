<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/api/posts')]
class PostController extends AbstractController
{
    private EntityManagerInterface $em;
    private PostRepository $postRepository;

    public function __construct(EntityManagerInterface $em, PostRepository $postRepository)
    {
        $this->em = $em;
        $this->postRepository = $postRepository;
    }

    private function uploadImage($image): ?string
    {
        if (!$image) {
            return null;
        }

        $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/posts';
        $filename = uniqid() . '.' . $image->guessExtension();

        try {
            $image->move($uploadsDirectory, $filename);
        } catch (FileException $e) {
            return null;
        }

        return '/uploads/posts/' . $filename;
    }

    #[Route('', name: 'post_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $content = $request->request->get('content');
        if (!$content) {
            return $this->json(['success' => false, 'message' => 'Content is required'], 400);
        }

        $imageFile = $request->files->get('image');
        $imageUrl = $this->uploadImage($imageFile);

        $post = new Post();
        $post->setUser($user);
        $post->setContent($content);
        $post->setImageUrl($imageUrl);
        $post->setLikesCount(0);
        $post->setCommentsCount(0);
        $post->setSharesCount(0);
        $post->setCreatedAt(new \DateTime());
        $post->setUpdatedAt(new \DateTime());

        $this->em->persist($post);
        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'Post created successfully']);
    }

    #[Route('/{postId<\d+>}', name: 'post_get', methods: ['GET'])]
public function getPost(int $postId): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $post = $this->postRepository->find($postId);

    if (!$post) {
        return $this->json(['success' => false, 'message' => 'Post not found'], 404);
    }

    $postData = [
        'id' => $post->getId(),
        'content' => $post->getContent(),
        'imageUrl' => $post->getImageUrl(),
        'likesCount' => $post->getLikesCount(),
        'commentsCount' => $post->getCommentsCount(),
        'sharesCount' => $post->getSharesCount(),
        'createdAt' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
        'updatedAt' => $post->getUpdatedAt()->format('Y-m-d H:i:s'),
        'user' => [
            'id' => $post->getUser()->getId(),
            'email' => $post->getUser()->getEmail()
        ]
    ];

    return $this->json([
        'success' => true,
        'post' => $postData
    ]);
}


    #[Route('/{postId<\d+>}', name: 'post_update', methods: ['PUT', 'POST'])]
public function update(int $postId, Request $request): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $post = $this->postRepository->find($postId);
    if (!$post) {
        return $this->json(['success' => false, 'message' => 'Post not found'], 404);
    }

    if ($post->getUser()->getId() !== $user->getId()) {
        return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    $content = $request->get('content'); // Supports form-data
    if ($content !== null) {
        $post->setContent($content);
    }

    $image = $request->files->get('image');
    if ($image) {
        $imageUrl = $this->uploadImage($image);
        $post->setImageUrl($imageUrl);
    }

    $post->setUpdatedAt(new \DateTime());

    $this->em->flush();
    $this->em->clear(); // Force reload fresh data

    $post = $this->postRepository->find($postId);

    return $this->json([
        'success' => true,
        'message' => 'Post updated successfully',
        'post' => [
            'id' => $post->getId(),
            'content' => $post->getContent(),
            'imageUrl' => $post->getImageUrl(),
            'likesCount' => $post->getLikesCount(),
            'commentsCount' => $post->getCommentsCount(),
            'sharesCount' => $post->getSharesCount(),
            'createdAt' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $post->getUpdatedAt()->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $post->getUser()->getId(),
                'email' => $post->getUser()->getEmail(),
            ]
        ]
    ]);
}

    #[Route('/{postId<\d+>}', name: 'post_delete', methods: ['DELETE'])]
public function delete(int $postId): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $post = $this->postRepository->find($postId);
    if (!$post) {
        return $this->json(['success' => false, 'message' => 'Post not found'], 404);
    }

    if ($post->getUser()->getId() !== $user->getId()) {
        return $this->json(['success' => false, 'message' => 'Forbidden: You cannot delete this post'], 403);
    }

    // Optional: remove the image file too
    if ($post->getImageUrl()) {
        $filePath = $this->getParameter('kernel.project_dir') . '/public' . $post->getImageUrl();
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $this->em->remove($post);
    $this->em->flush();

    return $this->json([
        'success' => true,
        'message' => 'Post deleted successfully'
    ]);
}

   #[Route('/my', name: 'post_my', methods: ['GET'])]
public function myPosts(Request $request): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $limit = $request->query->get('limit', 20);
    $offset = $request->query->get('offset', 0);

    $posts = $this->postRepository->findUserPosts($user, $limit, $offset);

    $results = [];
    foreach ($posts as $post) {
        $results[] = [
            'id' => $post->getId(),
            'content' => $post->getContent(),
            'imageUrl' => $post->getImageUrl(),
            'likesCount' => $post->getLikesCount(),
            'commentsCount' => $post->getCommentsCount(),
            'sharesCount' => $post->getSharesCount(),
            'createdAt' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $post->getUpdatedAt()->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $post->getUser()->getId(),
                'email' => $post->getUser()->getEmail(),
            ]
        ];
    }

    return $this->json([
        'success' => true,
        'count' => count($results),
        'posts' => $results,
    ]);
}


    
}
