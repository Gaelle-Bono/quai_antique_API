<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use DateTimeImmutable;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;


#[Route('api/category', name:'app_api_category_')]
Class CategoryController extends AbstractController
{

    public function __construct(
            private EntityManagerInterface $manager, 
            private CategoryRepository $repository, 
            private SerializerInterface $serialiser,
            private UrlGeneratorInterface $urlGenerator
        )
    {
    }


    #[Route(name:'new', methods:'POST')]
    public function new(Request $request): JsonResponse
    {
        $category = $this->serialiser ->deserialize($request->getContent(), Category::class, 'json');
        $category->setCreatedAt(new DateTimeImmutable());

         // Tell Doctrine you want to (eventually) save the Category (no queries yet)
        $this->manager->persist($category);
        // Actually executes the queries (i.e. the INSERT query)
        $this->manager->flush();
        
        $responseData = $this->serialiser ->serialize($category, 'json');
        $location = $this->urlGenerator->generate(
            'app_api_category_show',
            ['id' => $category->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        return new JsonResponse($responseData, Response::HTTP_CREATED, ["Location" => $location], true);

    }

    #[Route('/{id}', name: 'show', methods: 'GET')]
    public function show(int $id): JsonResponse
    {
        $category = $this->repository->findOneBy(['id' => $id]);
        if ($category) {
            $responseData = $this->serialiser ->serialize($category, 'json');
            return new JsonResponse($responseData, Response::HTTP_OK,[], json:true);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }


    #[Route('/{id}', name: 'edit', methods: 'PUT')]

    public function edit(int $id, Request $request): JsonResponse
    {
        $category = $this->repository->findOneBy(['id' => $id]);
        if ($category) {
            $category = $this->serialiser ->deserialize(
                $request->getContent(), 
                Category::class, 
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $category]);

            $category->setUpdatedAt(new DateTimeImmutable());

        $this->manager->flush();

        $responseData = $this->serialiser ->serialize($category, 'json');

            return new JsonResponse($responseData, Response::HTTP_OK,[], json:true);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }


    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $category = $this->repository->findOneBy(['id' => $id]);
        if ($category) {
            $this->manager->remove($category);
            $this->manager->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}
