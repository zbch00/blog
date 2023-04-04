<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function Symfony\Component\String\s;

class PostController extends AbstractController
{
    private PostRepository $postRepository;
    private SerializerInterface $serializer ;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    /**
     * @param PostRepository $postRepository
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     */
    public function __construct(PostRepository $postRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->postRepository = $postRepository;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }


    #[Route('/api/posts', name: 'api_getPosts', methods: ['GET'])]
    public function getPosts(): Response
    {
        // Rechercher les posts dans la BD
        $posts = $this->postRepository->findAll();
        if (!$posts) {
            return $this->generateError("Le post demandé n'existe pas",Response::HTTP_NOT_FOUND);
        }
        // Normaliser le tableau $posts
        // -> transformer $posts en un tableau associatif
        // $postsArray = $normalizer->normalize($posts);
        // Encoder en json
        // $postsJson = json_encode($postsArray);
        // Serialiser le tableau $posts en json
        $postsJson = $this->serializer->serialize($posts,'json', ['groups' => 'list_posts']);
        // Génerer la réponse http
        /* $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('content-type','application/json');
        $response->setContent($postsJson);
        return $response;
        */
        return new Response($postsJson,Response::HTTP_OK,
                    ['content-type' => 'application/json']);
    }

    #[Route('/api/post/{id}', name: 'api_getPost', methods: ['GET'])]
    public function getPost(int $id): Response
    {
        $post = $this->postRepository->find($id);
        // Génerer un erreur si le post recherché n'existe pas
        if (!$post) {
            return $this->generateError("Le post demandé n'existe pas",Response::HTTP_NOT_FOUND);
        }
        $postJson = $this->serializer->serialize($post,'json',['groups' => 'get_post']);
        return new Response($postJson,Response::HTTP_OK,
            ['content-type' => 'application/json']);
    }

    #[Route('/api/posts', name: 'api_createPost', methods: ['POST'])]
    public function createPost(Request $request):Response
    {
     // Récuperer dans la requête le body contenant le json du nouveau post
        $bodyRequest = $request->getContent();
     // Deserializer le json en un objet de la classe Post
        try {
            // Surveiller si le code ci-dessous lève une exception
            $post = $this->serializer->deserialize($bodyRequest, Post::class, "json");

        } catch(NotEncodableValueException $exception){
            return $this->generateError("La requete n'est pas valide",Response::HTTP_BAD_REQUEST);
        }
     // Validation des données ($post) en fonction des règles de validation définies
        $erreurs = $this->validator->validate($post);
     // Tester s'il y a des erreurs
        if (count($erreurs) >0){
            // Transformer le tableau en json
            $erreursJson = $this->serializer->serialize($erreurs,'json');
            return new Response($erreursJson, Response::HTTP_BAD_REQUEST,
            ["content-type" => "application/json"]);
        }


     // Inserer le nouveau post dans la BD
        $post->setCreatedAt(new \DateTime());
        $this->entityManager->persist($post); // creer INSERT
        $this->entityManager->flush();
     // Génerer la response HTTP
     // Sérializer $post en json
        $postJson = $this->serializer->serialize($post,'json');
        return new Response($postJson, Response::HTTP_CREATED,["content-type" => "application/json"]);
    }

    #[Route('/api/post/{id}', name: 'api_deletePost', methods: ['DELETE'])]
    public function deletePost(int $id):Response
    {
        $post = $this->postRepository->find($id);
        if (!$post) {
            return $this->generateError("Le post à supprimer n'existe pas",Response::HTTP_NOT_FOUND);
        }
        $this->entityManager->remove($post);
        $this->entityManager->flush();
        return new Response(null,Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/post/{id}', name: 'api_updatePost', methods: ['PUT'])]
    public function updatePost(int $id, Request $request):Response
    {
        // Récuperer le body de la requête
        $bodyRequest = $request->getContent();
        // Récuperer dans la BD le post à modifier
        $post = $this->postRepository->find($id);
        if (!$post) {
            return $this->generateError("Le post à modifier n'existe pas",Response::HTTP_NOT_FOUND);
        }
        // Modifier le post avec les données du body
        try {
            // Surveiller si le code ci-dessous lève une exception
            $this->serializer->deserialize($bodyRequest, Post::class,'json',['object_to_populate' => $post]);

        } catch(NotEncodableValueException $exception){
            return $this->generateError("La requete n'est pas valide",Response::HTTP_BAD_REQUEST);
        }
        return new Response(null,Response::HTTP_NO_CONTENT);
    }

    private function generateError(string $message, int $status) :Response {
        $erreur = [
            'status' => $status,
            'message' => $message
        ];
        return new Response(json_encode($erreur), $status,
        ["content-type" => 'application/json']);
    }

}



