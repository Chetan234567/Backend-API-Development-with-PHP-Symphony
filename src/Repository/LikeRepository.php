<?php

namespace App\Repository;

use App\Entity\Like;
use App\Entity\User;
use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Like::class);
    }

    public function isLiked(User $user, Post $post): bool
{
    $result = $this->createQueryBuilder('l')
        ->where('l.user = :user')
        ->andWhere('l.post = :post')
        ->setParameter('user', $user)
        ->setParameter('post', $post)
        ->getQuery()
        ->getOneOrNullResult();

    return $result !== null;
}

public function findUserLike(User $user, Post $post): ?Like
{
    return $this->createQueryBuilder('l')
        ->where('l.user = :user')
        ->andWhere('l.post = :post')
        ->setParameter('user', $user)
        ->setParameter('post', $post)
        ->getQuery()
        ->getOneOrNullResult();
}


    public function findLikesByPost(Post $post)
    {
        return $this->createQueryBuilder('l')
            ->where('l.post = :post')
            ->setParameter('post', $post)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countLikesByPost(Post $post): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
