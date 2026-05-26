<?php

namespace App\Repository;

use App\Entity\AenderungsLabel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AenderungsLabel>
 *
 * @method AenderungsLabel|null find($id, $lockMode = null, $lockVersion = null)
 * @method AenderungsLabel|null findOneBy(array $criteria, array $orderBy = null)
 * @method AenderungsLabel[]    findAll()
 * @method AenderungsLabel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AenderungsLabelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AenderungsLabel::class);
    }

    public function save(AenderungsLabel $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AenderungsLabel $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
