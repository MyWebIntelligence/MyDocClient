<?php

namespace App\Controller\User;

use App\Controller\Traits\Authorization;
use App\Entity\Permission;
use App\Entity\Project;
use App\Entity\User;
use App\Form\ImportDocumentType;
use App\Form\LexiconType;
use App\Form\ProjectType;
use App\Repository\AnnotationRepository;
use App\Repository\DocumentRepository;
use App\Repository\TagRepository;
use App\Service\AnnotationService;
use App\Service\DocumentService;
use App\Service\TextProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use JsonException;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("IS_AUTHENTICATED")
 */
class ProjectController extends AbstractController
{

    public const RESTRICT_ACCESS_MESSAGE = "Vous n'êtes pas autorisé à agir sur ce projet.";

    use Authorization;

    /**
     * @Route("/projects", name="user_projects")
     */
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createFormBuilder()
            ->add('project', ProjectType::class, ['data' => new Project()])
            ->add('import', ImportDocumentType::class, ['required' => false])
            ->getForm();

        $editableProject = [];
        $readableProject = [];

        foreach ($user->getPermissions() as $permission) {
            if ($this->isGrantedProject($user, $permission->getProject(), Permission::ROLE_EDITOR)) {
                $editableProject[] = $permission->getProject();
            } elseif ($this->isGrantedProject($user, $permission->getProject(), Permission::ROLE_READER)) {
                $readableProject[] = $permission->getProject();
            }
        }

        return $this->render('user/project/index.html.twig', [
            'projects' => $user->getProjects(),
            'editableProjects' => $editableProject,
            'readableProjects' => $readableProject,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/projects/new", name="user_new_project")
     */
    public function new(
        Request $request,
        DocumentService $documentService,
        EntityManagerInterface $entityManager): Response
    {
        $project = new Project();
        $form = $this->createFormBuilder()
            ->add('project', ProjectType::class, ['data' => $project])
            ->add('import', ImportDocumentType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $project->setLinkHash();
                $entityManager->persist($project);
                $entityManager->flush();

                // Import documents after project is saved (flushed)
                [$succeeded, $errors] = $documentService->importDocuments($project, $form->get('import'));
                $entityManager->flush();

                $message = empty($errors)
                    ? sprintf('%s document(s) importé(s)', count($succeeded))
                    : sprintf('%s document(s) importés, %s erreur(s)', count($succeeded), count($errors));
                $this->addFlash('info', $message);

                if (!empty($errors)) {
                    foreach ($errors as $file => $error) {
                        $this->addFlash('danger', sprintf('%s : %s', $file, $error));
                    }
                }

                return $this->redirectToRoute('user_view_project', ['id' => $project->getId()]);
            }

            $this->addFlash('danger', "Le projet n'a pas pu être sauvegardé.");
        }

        return $this->render('user/project/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/project/{id}",
     *     name="user_view_project",
     *     requirements={"id": "\d+"},
     *     methods={"GET"})
     * @throws JsonException
     */
    public function view(
        Request $request,
        Project $project,
        DocumentRepository $documentRepository,
        EntityManagerInterface $entityManager,
        TagRepository $tagRepository,
        PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canRead($user, $project)) {
            $this->addFlash("danger", self::RESTRICT_ACCESS_MESSAGE);
            return $this->redirectToRoute('home');
        }

        $queryBuilder = $documentRepository->getSearchDocumentsQueryBuilder($project, $request);

        $documents = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            25,
            $request->query->get('q') ? [] : ['defaultSortFieldName' => 'd.id', 'defaultSortDirection' => 'asc']
        );

        $editForm = $this->createForm(ProjectType::class, $project);
        $importForm = $this->createForm(ImportDocumentType::class, null, [
            'action' => $this->generateUrl('user_import_documents', ['id' => $project->getId()])
        ]);

        $response = $this->render('user/project/view.html.twig', [
            'project' => $project,
            'projectRole' => $this->getRole($user, $project),
            'documents' => $documents,
            'tagTree' => $tagRepository->getProjectTags($project, true),
            'canEdit' => $this->canEdit($user, $project),
            'editForm' => $editForm->createView(),
            'importForm' => $importForm->createView(),
            'search' => $request->query->get('q'),
        ]);

        $cookie = Cookie::create('searchParams', json_encode($request->query->all(), JSON_THROW_ON_ERROR));
        $response->headers->setCookie($cookie);

        return $response;
    }

    /**
     * @Route("/project/{id}",
     *     name="user_edit_project",
     *     requirements={"id": "\d+"},
     *     methods={"POST"})
     */
    public function edit(Project $project, Request $request, ManagerRegistry $doctrine): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canEdit($user, $project)) {
            $this->addFlash("danger", self::RESTRICT_ACCESS_MESSAGE);
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($project);
            $entityManager->flush();
            $this->addFlash('success', "Le projet a été sauvegardé.");
        }

        return $this->redirectToRoute('user_view_project', ['id' => $project->getId()]);
    }

    /**
     * @Route("/project/{id}/word-count",
     *     name="user_project_lexicon",
     *     requirements={"id": "\d+"})
     */
    public function lexicon(Project $project, Request $request, TextProcessor $textProcessor): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canRead($user, $project)) {
            $this->addFlash("danger", self::RESTRICT_ACCESS_MESSAGE);
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(LexiconType::class, null, [
            'method' => 'get',
        ]);

        $form->handleRequest($request);
        $words = $textProcessor->countWords(array_merge([], ...$project->getWords()));
        $totalWords = count($words);

        if (!$form->isSubmitted()) {
            $defaultData = [];

            foreach ($form as $element) {
                $defaultData[$element->getName()] = $element->getData();
            }

            $form->setData($defaultData);
        }

        $data = $form->getData();

        $words = array_filter($words, static function ($count, $word) use ($data) {
            return ($count >= $data['minCount']) && !is_numeric($word);
        }, ARRAY_FILTER_USE_BOTH);
        $filteredWords = count($words);

        if ($data['sort'] === 'word') {
            ksort($words, SORT_STRING | SORT_FLAG_CASE);
        } elseif ($data['sort'] === 'count') {
            arsort($words, SORT_NUMERIC);
        }

        return $this->render('user/project/lexicon.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
            'lexicon' => array_slice($words, 0, $data['limit']),
            'totalWords' => $totalWords,
            'filterWords' => $filteredWords,
        ]);
    }

    /**
     * @Route("/project/{id}/delete",
     *     name="user_delete_project",
     *     requirements={"id": "\d+"})
     */
    public function delete(Project $project, Request $request, ManagerRegistry $doctrine): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isProjectOwner($user, $project)) {
            $this->addFlash("danger", self::RESTRICT_ACCESS_MESSAGE);
            return $this->redirectToRoute('home');
        }

        if ($request->isMethod('post')) {
            $projectName = $project->getName();
            $entityManager = $doctrine->getManager();
            $entityManager->remove($project);
            $entityManager->flush();
            $this->addFlash('success', sprintf('Le projet "%s" a été supprimé.', $projectName));
            return $this->redirectToRoute('user_projects');
        }

        return $this->render('user/project/delete.html.twig', [
            'project' => $project,
        ]);
    }

    /**
     * @Route("/project/{id}/annotations", name="user_project_annotations")
     */
    public function annotations(
        Project $project,
        AnnotationRepository $annotationRepository,
        AnnotationService $annotationService): Response
    {
        $annotations = $annotationRepository->getProjectAnnotations($project);

        return $this->render('user/project/annotations.html.twig', [
            'authors' => $annotationService->getAuthors($annotations),
            'annotationsByTag' => $annotationService->getTagIndexed($annotations),
            'annotationAuthors' => $annotationService->getAuthors($annotations),
            'project' => $project,
            'document' => null,
        ]);
    }

}
