<?php

namespace App\Repository;

use App\Entity\Follow;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FollowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Follow::class);
    }

    public function findFollowers(User $user)
    {
        return $this->createQueryBuilder('f')
            ->where('f.following = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFollowing(User $user)
    {
        return $this->createQueryBuilder('f')
            ->where('f.follower = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function isFollowing(User $follower, User $following): bool
    {
        $result = $this->createQueryBuilder('f')
            ->where('f.follower = :follower')
            ->andWhere('f.following = :following')
            ->setParameter('follower', $follower)
            ->setParameter('following', $following)
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }
}