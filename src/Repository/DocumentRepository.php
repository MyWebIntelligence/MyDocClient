<?php

namespace App\Repository;

use App\Entity\Document;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method Document|null find($id, $lockMode = null, $lockVersion = null)
 * @method Document|null findOneBy(array $criteria, array $orderBy = null)
 * @method Document[]    findAll()
 * @method Document[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    public function getSearchDocumentsQueryBuilder(Project $project, Request $request): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('d')
            ->select()
            ->where('d.project = :project')
            ->setParameter('project', $project);

        if ($request->query->get('q')) {
            $queryBuilder
                ->andWhere('MATCH_AGAINST(d.title, d.description, d.content) AGAINST (:search boolean) > 0')
                ->orderBy('MATCH_AGAINST(d.title, d.description, d.content) AGAINST (:search boolean)', 'DESC')
                ->setParameter('search', $request->query->get('q'));
        }

        dump($queryBuilder->getQuery()->getSQL());

        return $queryBuilder;
    }

    /**
     * @throws Exception
     */
    public function getSiblingDocument(Document $document, Request $request, int $offset): ?Document
    {
        if ($searchParams = $request->cookies->get('searchParams')) {
            $searchData = json_decode($searchParams, true, 512, JSON_THROW_ON_ERROR);
            foreach ($searchData as $param => $value) {
                $request->query->set($param, $value);
            }
        }

        $searchTerm = $request->query->get('q', '');

        $lead = sprintf('LEAD(d.id, %s) OVER (ORDER BY %s %s) AS sibling',
            $offset,
            $request->query->get('sort', 'd.id'),
            strtoupper($request->query->get('direction', 'ASC'))
        );

        $termClause = $searchTerm ? sprintf(
        'AND MATCH (d.title, d.description, d.content) AGAINST ("%s" IN BOOLEAN MODE) > 0
                ORDER BY MATCH (d.title, d.description, d.content) AGAINST ("%s" IN BOOLEAN MODE) DESC',
            $searchTerm,
            $searchTerm
        ) : '';

        $sql = sprintf('
            SELECT sibling FROM (
                SELECT %s, d.id
                FROM document AS d
                WHERE d.project_id = :project
                %s
            ) AS t
            WHERE t.id = :document', $lead, $termClause);

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);

        $result = $stmt->executeQuery([
            'document' => $document->getId(),
            'project' => $document->getProject()->getId(),
        ]);

        if ($id = $result->fetchOne()) {
            return $this->find($id);
        }

        return null;
    }

    // /**
    //  * @return Document[] Returns an array of Document objects
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
    public function findOneBySomeField($value): ?Document
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
