<?php

namespace App\Repository;

use App\Entity\Annotation;
use App\Entity\Document;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method Annotation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Annotation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Annotation[]    findAll()
 * @method Annotation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnnotationRepository extends ServiceEntityRepository
{

    private ProjectRepository $projectRepository;
    private DocumentRepository $documentRepository;
    private UserRepository $userRepository;
    private TagRepository $tagRepository;

    public function __construct(
        ManagerRegistry $registry,
        ProjectRepository $projectRepository,
        DocumentRepository $documentRepository,
        UserRepository $userRepository,
        TagRepository $tagRepository)
    {
        $this->projectRepository = $projectRepository;
        $this->documentRepository = $documentRepository;
        $this->userRepository = $userRepository;
        $this->tagRepository = $tagRepository;

        parent::__construct($registry, Annotation::class);
    }

    public function getProjectAnnotations(Project $project)
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->join(Document::class, 'd', 'WITH', 'd = a.document')
            ->join(Project::class, 'p', 'WITH', 'p = d.project')
            ->where('p = :project')
            ->setParameter('project', $project)
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    public function getFiltered(Request $request, Project $project = null)
    {
        $params = $request->query;
        $project = $project ?? $this->projectRepository->find($params->get('project'));

        $queryBuilder = $this->createQueryBuilder('a')
            ->join(Document::class, 'd', 'WITH', 'd = a.document')
            ->join(Project::class, 'p', 'WITH', 'p = d.project')
            ->where('p = :project')
            ->setParameter('project', $project)
        ;

        if ($params->get('document')) {
            $document = $this->documentRepository->find($params->get('document'));
            if ($document && $document->getProject() === $project) {
                $queryBuilder->andWhere('d = :document')
                    ->setParameter('document', $document);
            }
        }

        if ($params->get('author')) {
            $user = $this->userRepository->find($params->get('author'));
            if ($user) {
                $queryBuilder->andWhere('a.createdBy = :user')
                    ->setParameter('user', $user);
            }
        }

        if ($params->get('tag')) {
            $tag = $this->tagRepository->find($params->get('tag'));
            if ($tag) {
                $queryBuilder->andWhere('a.tag = :tag')
                    ->setParameter('tag', $tag);
            }
        }

        return $queryBuilder->getQuery()->getResult();

    }

    // /**
    //  * @return Annotation[] Returns an array of Annotation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Annotation
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
