<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Tree\Hydrator\ORM\TreeObjectHydrator;

/**
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function getProjectTags(Project $project, bool $tree = false)
    {
        $treeRepository = $this->getEntityManager()->getRepository(Tag::class);

        $query = $treeRepository->createQueryBuilder('t')
            ->where('t.root = :project')
            ->orderBy('t.lvl', 'ASC')
            ->addOrderBy('t.lft', 'ASC')
            ->setParameter('project', $project)
            ->getQuery();

        if ($tree === true) {
            $this->getEntityManager()
                ->getConfiguration()
                ->addCustomHydrationMode('tree', TreeObjectHydrator::class);

            return $query->setHint(Query::HINT_INCLUDE_META_COLUMNS, true)
                ->getResult('tree');
        }

        return $query->getResult();
    }

    // /**
    //  * @return Tag[] Returns an array of Tag objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Tag
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
