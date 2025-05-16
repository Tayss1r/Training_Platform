<?php

namespace App\Repository;

use App\Entity\Announcement;
use App\Entity\AnnouncementRead;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnnouncementRead>
 *
 * @method AnnouncementRead|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnnouncementRead|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnnouncementRead[]    findAll()
 * @method AnnouncementRead[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnnouncementReadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnnouncementRead::class);
    }

    /**
     * Check if an announcement has been read by a user
     */
    public function isReadByUser(Announcement $announcement, User $user): bool
    {
        $result = $this->createQueryBuilder('ar')
            ->select('COUNT(ar.id)')
            ->where('ar.announcement = :announcement')
            ->andWhere('ar.user = :user')
            ->setParameter('announcement', $announcement)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result > 0;
    }
}
