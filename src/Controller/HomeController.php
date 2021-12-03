<?php

namespace App\Controller;

use App\Data\SearchDataProject;
use App\Entity\Categorie;
use App\Entity\Edition;
use App\Entity\File;
use App\Entity\Project;
use App\Form\ProjectType;
use App\Form\Search\SearchProjectForm;
use App\Repository\AboutRepository;
use App\Repository\CategorieRepository;
use App\Repository\EditionRepository;
use App\Repository\PartenairesRepository;
use App\Repository\ProjectRepository;
use App\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request, AboutRepository $aboutRepository, CategorieRepository $categorieRepository, EditionRepository $editionRepository, ProjectRepository $projectRepository, PartenairesRepository $partenairesRepository): Response
    {
        $projects = $projectRepository->findAll();
        $abouts = $aboutRepository->findAll();
        $partenaires = $partenairesRepository->findAll();
        $categories = $categorieRepository->findLatest();
        $editions = $editionRepository->findAll();

        $data = new SearchDataProject();
        $data->page = $request->get('page', 1);
        $form = $this->createForm(SearchProjectForm::class, $data);
        $form->handleRequest($request);
        return $this->render('home/index.html.twig', [
            'categories' => $categories,
            'abouts' => $abouts,
            'editions' => $editions,
            'projects' => $projects,
            'partenaires' => $partenaires,
            'form' => $form->createView()
        ]);
    }

    #[Route('/sdg', name: 'all_sdg', methods: ['GET'])]
    public function allSdg(CategorieRepository $categorieRepository): Response
    {
        return $this->render('categorie/allSDG.html.twig', [
            'categories' => $categorieRepository->findAll(),
        ]);
    }

    #[Route('/editions', name: 'all_editions', methods: ['GET'])]
    public function allEditions(EditionRepository $editionRepository): Response
    {
        return $this->render('edition/allEditions.html.twig', [
            'categories' => $editionRepository->findAll(),
        ]);
    }

    #[Route('/editions/{slug}', name: 'edition_page', methods: ['GET'])]
    public function OneEdition(Edition $edition): Response
    {
        return $this->render('edition/page.html.twig', [
            'edition' => $edition
        ]);
    }

    #[Route('/about-us', name: 'about-page', methods: ['GET'])]
    public function about(TeamRepository $teamRepository, AboutRepository $aboutRepository): Response
    {
        $teams = $teamRepository->findAll();

        return $this->render('home/about_page.html.twig', [
            'about' => $aboutRepository->findOneBy(['id' => 1]),
            'teams' => $teams,
        ]);
    }

    #[Route('/sdg/{slug}', name: 'sdg_page', methods: ['GET'])]
    public function OneSdg(Categorie $categorie, CategorieRepository $categorieRepository): Response
    {
        return $this->render('categorie/sdg-page.html.twig', [
            'categorie' => $categorie,
            'categories' => $categorieRepository->findAll(),
        ]);
    }

    #[Route('/projects', name: 'all_projects', methods: ['GET'])]
    public function allProjects(Request $request, ProjectRepository $projectRepository): Response
    {
        $data = new SearchDataProject();
        $data->page = $request->get('page', 1);
        $form = $this->createForm(SearchProjectForm::class, $data);
        $form->handleRequest($request);

        $projects = $projectRepository->findSearch($data);

        return $this->render('project/AllProjects.html.twig', [
            'projects' => $projects,
            'form' => $form->createView()
        ]);
    }

    #[Route('/project/{slug}', name: 'project_page', methods: ['GET'])]
    public function OneProject(Project $project, ProjectRepository $projectRepository): Response
    {
        $projects = $projectRepository->findAll();
        return $this->render('project/project-page.html.twig', [
            'project' => $project,
            'projects' => $projects,
        ]);
    }

    #[Route('/projects/new', name: 'project_new_home', methods: ['GET','POST'])]
    public function newProject(Request $request, ProjectRepository $projectRepository): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $images = $form->get('avatar')->getData();
            foreach($images as $image){
                $this->saveDoc($project, $image, File::TYPE_AVATAR);
            }
            $images = $form->get('images')->getData();
            foreach($images as $image){
                $this->saveDoc($project, $image, File::TYPE_ILLUSTRATION);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'Ajout réussi');

            return $this->redirectToRoute('project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('home/project_new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    public function saveDoc($project, $image, $type)
    {
        $user = $this->getUser();
        $fichier = md5(uniqid()) . '.' . $image->guessExtension();
        $nomFichier = $image->getClientOriginalName();
        $image->move($this->getParameter('files_directory'), $fichier);
        $img = new File();

        $img->setNom($fichier);
        $img->setUser($user);
        $img->setNomFichier($nomFichier);
        if ($type == File::TYPE_AVATAR){
            $img->setProjectAvatar($project);
            $img->setType($type);
            $project->addLogo($img);
        }
        if ($type == File::TYPE_ILLUSTRATION){
            $img->setProject($project);
            $img->setType($type);
            $project->addDocument($img);
        }
    }

    #[Route('/legal-mentions', name: 'mentions')]
    public function mentions(): Response
    {
        return $this->renderForm('home/mentions.html.twig', [
        ]);
    }
}
