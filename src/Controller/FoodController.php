<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Food;
use App\Repository\FoodRepository;
use DateTimeImmutable;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;


#[Route('api/food', name:'app_api_food_')]
Class FoodController extends AbstractController
{

    public function __construct(
            private EntityManagerInterface $manager, 
            private FoodRepository $repository, 
            private SerializerInterface $serialiser,
            private UrlGeneratorInterface $urlGenerator
        )
    {
    }


    #[Route(name:'new', methods:'POST')]
    public function new(Request $request): JsonResponse
    {
        $food = $this->serialiser ->deserialize($request->getContent(), Food::class, 'json');
        $food->setCreatedAt(new DateTimeImmutable());

         // Tell Doctrine you want to (eventually) save the Food (no queries yet)
        $this->manager->persist($food);
        // Actually executes the queries (i.e. the INSERT query)
        $this->manager->flush();
        
        $responseData = $this->serialiser ->serialize($food, 'json');
        $location = $this->urlGenerator->generate(
            'app_api_food_show',
            ['id' => $food->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        return new JsonResponse($responseData, Response::HTTP_CREATED, ["Location" => $location], true);

    }

    #[Route('/{id}', name: 'show', methods: 'GET')]
    public function show(int $id): JsonResponse
    {
        $food = $this->repository->findOneBy(['id' => $id]);
        if ($food) {
            $responseData = $this->serialiser ->serialize($food, 'json');
            return new JsonResponse($responseData, Response::HTTP_OK,[], json:true);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }


    #[Route('/{id}', name: 'edit', methods: 'PUT')]

    public function edit(int $id, Request $request): JsonResponse
    {
        $food = $this->repository->findOneBy(['id' => $id]);
        if ($food) {
            $food = $this->serialiser ->deserialize(
                $request->getContent(), 
                Food::class, 
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $food]);

            $food->setUpdatedAt(new DateTimeImmutable());

        $this->manager->flush();

        $responseData = $this->serialiser ->serialize($food, 'json');

            return new JsonResponse($responseData, Response::HTTP_OK,[], json:true);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }


    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $food = $this->repository->findOneBy(['id' => $id]);
        if ($food) {
            $this->manager->remove($food);
            $this->manager->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}
