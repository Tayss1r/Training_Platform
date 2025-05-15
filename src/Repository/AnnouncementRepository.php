<?php

namespace App\Repository;

use App\Entity\Announcement;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Announcement>
 *
 * @method Announcement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Announcement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Announcement[]    findAll()
 * @method Announcement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnnouncementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Announcement::class);
    }

    /**
     * Find announcements for sessions that a user is enrolled in
     */
    public function findByEnrolledUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.session', 's')
            ->join('s.enrollments', 'e')
            ->where('e.users = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find unread announcements for a user
     */
    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.session', 's')
            ->join('s.enrollments', 'e')
            ->leftJoin('a.announcementReads', 'ar', 'WITH', 'ar.user = :user')
            ->where('e.users = :user')
            ->andWhere('ar.id IS NULL')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find announcements for a specific session
     */
    public function findBySession(Session $session): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.session = :session')
            ->setParameter('session', $session)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
