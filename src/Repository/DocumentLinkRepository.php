<?php

namespace App\Repository;

use App\Entity\DocumentLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DocumentLink|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentLink|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentLink[]    findAll()
 * @method DocumentLink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentLink::class);
    }

    // /**
    //  * @return DocumentLink[] Returns an array of DocumentLink objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DocumentLink
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
