<?php

namespace App\Repository;

use App\Entity\StundenplanNeu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StundenplanNeu>
 *
 * @method StundenplanNeu|null find($id, $lockMode = null, $lockVersion = null)
 * @method StundenplanNeu|null findOneBy(array $criteria, array $orderBy = null)
 * @method StundenplanNeu[]    findAll()
 * @method StundenplanNeu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StundenplanNeuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StundenplanNeu::class);
    }

//    /**
//     * @return StundenplanNeu[] Returns an array of StundenplanNeu objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?StundenplanNeu
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
