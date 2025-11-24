<?php

namespace App\Controller;

use App\Entity\Follow;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users')]
class FollowController extends AbstractController
{
    #[Route('/{userId<\d+>}/follow', name: 'user_follow', methods: ['POST'])]
    public function followUser(int $userId, EntityManagerInterface $em): JsonResponse
    {
        $currentUser = $this->getUser();
        $userToFollow = $em->getRepository(User::class)->find($userId);

        if (!$userToFollow) {
            return $this->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if ($userToFollow->getId() === $currentUser->getId()) {
            return $this->json(['success' => false, 'message' => 'Cannot follow yourself'], 400);
        }

        $followRepository = $em->getRepository(Follow::class);
        if ($followRepository->isFollowing($currentUser, $userToFollow)) {
            return $this->json(['success' => false, 'message' => 'Already following this user'], 400);
        }

        $follow = new Follow();
        $follow->setFollower($currentUser);
        $follow->setFollowing($userToFollow);
        $follow->setCreatedAt(new \DateTime());

        $em->persist($follow);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Successfully followed user',
            'follower_id' => $currentUser->getId(),
            'following_id' => $userToFollow->getId()
        ]);
    }

    #[Route('/{userId<\d+>}/unfollow', name: 'user_unfollow', methods: ['DELETE'])]
    public function unfollowUser(int $userId, EntityManagerInterface $em): JsonResponse
    {
        $currentUser = $this->getUser();
        $userToUnfollow = $em->getRepository(User::class)->find($userId);

        if (!$userToUnfollow) {
            return $this->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $follow = $em->getRepository(Follow::class)->createQueryBuilder('f')
            ->where('f.follower = :follower')
            ->andWhere('f.following = :following')
            ->setParameter('follower', $currentUser)
            ->setParameter('following', $userToUnfollow)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$follow) {
            return $this->json(['success' => false, 'message' => 'Not following this user'], 400);
        }

        $em->remove($follow);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Successfully unfollowed user'
        ]);
    }

    #[Route('/{userId<\d+>}/followers', name: 'user_followers', methods: ['GET'])]
    public function getFollowers(int $userId, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $follows = $em->getRepository(Follow::class)->findFollowers($user);

        $followers = [];
        foreach ($follows as $follow) {
            $followers[] = [
                'id' => $follow->getFollower()->getId(),
                'email' => $follow->getFollower()->getEmail(),
                'avatar' => $follow->getFollower()->getAvatarPath(),
            ];
        }

        return $this->json([
            'success' => true,
            'count' => count($followers),
            'followers' => $followers
        ]);
    }

    #[Route('/{userId<\d+>}/following', name: 'user_following', methods: ['GET'])]
    public function getFollowing(int $userId, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $follows = $em->getRepository(Follow::class)->findFollowing($user);

        $following = [];
        foreach ($follows as $follow) {
            $following[] = [
                'id' => $follow->getFollowing()->getId(),
                'email' => $follow->getFollowing()->getEmail(),
                'avatar' => $follow->getFollowing()->getAvatarPath(),
            ];
        }

        return $this->json([
            'success' => true,
            'count' => count($following),
            'following' => $following
        ]);
    }
}