<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Dto\CategoryCountPostDto;


class CategoryController extends AbstractController
{
    private CategoryRepository $categoryRepository;
    private SerializerInterface $serializer ;

    /**
     * @param CategoryRepository $categoryRepository
     * @param SerializerInterface $serializer
     */
    public function __construct(CategoryRepository $categoryRepository, SerializerInterface $serializer)
    {
        $this->categoryRepository = $categoryRepository;
        $this->serializer = $serializer;
    }


    #[Route('/api/categories', name: 'api_getCategories', methods: ['GET'])]
    public function getCategories(): Response
    {
        $categories = $this->categoryRepository->findAll();


        $categoriesJson = $this->serializer->serialize($categories,'json', ['groups'=> 'list_categories']);

        return new Response($categoriesJson,Response::HTTP_OK,
            ['content-type' => 'application/json']);
    }

    #[Route('/api/category/{id}/posts', name: 'api_getPostsByCategory', methods: ['GET'])]
    public function getPostsByCategory(int $id): Response
    {
        $category= $this->categoryRepository->find($id);
        if (!$category) {
            return $this->generateError("La categorie demandée n'existe pas",Response::HTTP_NOT_FOUND);
        }
        $postsCategory = $category->getPosts();
        $categoryJson = $this->serializer->serialize($postsCategory,'json',['groups' => 'get_posts_by_category']);
        return new Response($categoryJson,Response::HTTP_OK,
            ['content-type' => 'application/json']);
    }

    private function generateError(string $message, int $status) :Response {
        $erreur = [
            'status' => $status,
            'message' => $message
        ];
        return new Response(json_encode($erreur), $status,
            ["content-type" => 'application/json']);
    }

    #[Route('/api/categories/{id}', name: 'api_getCategory', methods: ['GET'])]
    public function getCategory(int $id): Response
    {
        $categories = $this->categoryRepository->find($id);
        $dto = new CategoryCountPostDto();
        $dto->setId($id);
        $dto->setTitle($categories->getTitle());
        $dto->setNbPosts(count($categories->getPosts()));
        $dtoJson = $this->serializer->serialize($dto,'json');

        return new Response($dtoJson,Response::HTTP_OK,
            ['content-type' => 'application/json']);
    }
}
