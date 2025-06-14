<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findAllTasks()
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findMainTasks()
    {
        return $this->createQueryBuilder('t')
            ->where('t.parentTask IS NULL')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus($status)
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.parentTask IS NULL')
            ->setParameter('status', $status)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findMyTasksByStatus($user, $status)
    {
        return $this->createQueryBuilder('t')
            ->join('t.assignedUser', 'u')
            ->where('u.id = :userId')
            ->andWhere('t.status = :status')
            //->andWhere('t.parentTask IS NULL')
            ->setParameter('userId', $user->getId(), 'uuid')
            ->setParameter('status', $status)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findMyTasks($user)
    {
        return $this->createQueryBuilder('t')
            ->join('t.assignedUser', 'u')
            ->where('u.id = :userId')
            ->andWhere('t.parentTask IS NULL')
            ->setParameter('userId', $user->getId(), 'uuid')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}