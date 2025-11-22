<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Follow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findUserPosts(User $user, int $limit = 20, int $offset = 0)
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findFeedPosts(User $user, int $limit = 20, int $offset = 0)
    {
        // Simple approach: Get posts from user and all followed users
        return $this->createQueryBuilder('p')
            ->innerJoin(Follow::class, 'f', 'WITH', 'p.user = f.following')
            ->where('f.follower = :user')
            ->orWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findPostsByUserId(int $userId, int $limit = 20, int $offset = 0)
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}