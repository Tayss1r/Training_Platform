<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Setting>
 *
 * @method Setting|null find($id, $lockMode = null, $lockVersion = null)
 * @method Setting|null findOneBy(array $criteria, array $orderBy = null)
 * @method Setting[]    findAll()
 * @method Setting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    /**
     * Find all settings by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.category = :category')
            ->setParameter('category', $category)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get a setting value by name
     */
    public function getSettingValue(string $name, $default = null): ?string
    {
        $setting = $this->findOneBy(['name' => $name]);
        
        if (!$setting) {
            return $default;
        }
        
        return $setting->getValue();
    }

    /**
     * Save a setting
     */
    public function saveSetting(string $name, string $value, string $category, ?string $description = null, ?string $type = null): Setting
    {
        $entityManager = $this->getEntityManager();
        
        $setting = $this->findOneBy(['name' => $name]);
        
        if (!$setting) {
            $setting = new Setting();
            $setting->setName($name);
            $setting->setCategory($category);
            
            if ($description) {
                $setting->setDescription($description);
            }
            
            if ($type) {
                $setting->setType($type);
            }
        }
        
        $setting->setValue($value);
        
        $entityManager->persist($setting);
        $entityManager->flush();
        
        return $setting;
    }
}
