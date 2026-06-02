<?php

namespace App\Repository;

use App\Entity\PersoenlicheDaten;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PersoenlicheDaten>
 *
 * @method PersoenlicheDaten|null find($id, $lockMode = null, $lockVersion = null)
 * @method PersoenlicheDaten|null findOneBy(array $criteria, array $orderBy = null)
 * @method PersoenlicheDaten[]    findAll()
 * @method PersoenlicheDaten[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PersoenlicheDatenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersoenlicheDaten::class);
    }

//    /**
//     * @return PersoenlicheDaten[] Returns an array of PersoenlicheDaten objects
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

//    public function findOneBySomeField($value): ?PersoenlicheDaten
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
