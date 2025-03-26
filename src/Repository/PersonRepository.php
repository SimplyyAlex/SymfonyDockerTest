<?php

namespace App\Repository;

use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Person>
 */
class PersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Person::class);
    }

    public function findSection(?int $minId, ?int $maxId, ?int $limit)
    {
        $query = $this->createQueryBuilder('p');

        if ($limit != null) {
            $query->setMaxResults($limit);
        }

        if ($minId != null) {
            $query->andWhere('p.id >= :minId');
            $query->setParameter('minId', $minId);
        }

        if ($maxId != null) {
            $query->andWhere('p.id <= :maxId');
            $query->setParameter('maxId', $maxId);
        }

        return $query->getQuery()->getResult();
    }

    //    /**
    //     * @return Person[] Returns an array of Person objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Person
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
