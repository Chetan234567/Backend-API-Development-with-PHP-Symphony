<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Post;
use App\Entity\Report;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/report')]
class ReportController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/user/{userId}', methods: ['POST'])]
    public function reportUser(int $userId, Request $request): JsonResponse
    {
        $reportedUser = $this->em->getRepository(User::class)->find($userId);
        $reason = json_decode($request->getContent(), true)['reason'] ?? null;

        if (!$reportedUser || !$reason) {
            return $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $report = new Report();
        $report->setReportedUser($reportedUser);
        $report->setReportedBy($this->getUser());
        $report->setReason($reason);
        $report->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($report);
        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'User reported successfully']);
    }

    #[Route('/post/{postId}', methods: ['POST'])]
    public function reportPost(int $postId, Request $request): JsonResponse
    {
        $post = $this->em->getRepository(Post::class)->find($postId);
        $reason = json_decode($request->getContent(), true)['reason'] ?? null;

        if (!$post || !$reason) {
            return $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $report = new Report();
        $report->setReportedPost($post);
        $report->setReportedBy($this->getUser());
        $report->setReason($reason);
        $report->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($report);
        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'Post reported successfully']);
    }
}
