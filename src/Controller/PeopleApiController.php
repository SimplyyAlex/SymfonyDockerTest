<?php

namespace App\Controller;

use App\Entity\Person;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

final class PeopleApiController extends AbstractController
{
    private LoggerInterface $logger;
    private SerializerInterface $serializer;

    public function __construct(#[Autowire(service: 'monolog.logger.api')] LoggerInterface $logger, SerializerInterface $serializer)
    {
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    #[OA\Post(
        description: 'Creates a new person by providing name and surname',
        summary: 'Create a new person',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'surname'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John'),
                    new OA\Property(property: 'surname', type: 'string', example: 'Doe'),
                ],
                type: 'object'
            )
        ),
        tags: ['People'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Person created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'John'),
                        new OA\Property(property: 'surname', type: 'string', example: 'Doe'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid input'
            )
        ]
    )]
    #[Route('/api/people', name: 'post_people_api', methods: ['POST'])]
    public function postPerson(
        Request $request,
        EntityManagerInterface $manager,
        ValidatorInterface $validator,
    ): Response
    {
        $this->logger->info('POST /api/people', ['body' => $request->getContent()]);
        $person = $this->serializer->deserialize($request->getContent(), Person::class, 'json');

        $errors = $validator->validate($person);
        if (count($errors) > 0) {
            $this->logger->error('POST /api/people VALIDATION FAILED');
            return new JsonResponse(['error' => "Invalid input"], Response::HTTP_BAD_REQUEST);
        }

        $manager->persist($person);
        $manager->flush();
        $this->logger->info('POST /api/people OK', ['person' => $this->serializer->serialize($person, 'json')]);
        return $this->json($person, Response::HTTP_CREATED);
    }

    #[OA\Get(
        description: 'Returns a list of people from the database. You can filter results using optional query parameters: limit, min_id, max_id.',
        summary: 'Retrieves a list of people',
        tags: ['People'],
        parameters: [
            new OA\Parameter(
                name: 'limit',
                description: 'Maximum number of people to retrieve',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'min_id',
                description: 'Minimum ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'max_id',
                description: 'Maximum ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of people retrieved successfully',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'John'),
                            new OA\Property(property: 'surname', type: 'string', example: 'Doe'),
                        ],
                        type: 'object'
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: 'No people found'
            )
        ]
    )]
    #[Route('/api/people', name: 'get_people_api', methods: ['GET'])]
    public function getPerson(EntityManagerInterface $manager, Request $request): Response
    {
        $repository = $manager->getRepository(Person::class);

        $limit = $request->query->getInt('limit');
        $minId = $request->query->getInt('min_id');
        $maxId = $request->query->getInt('max_id');
        $this->logger->info("GET /api/people LIMIT=$limit MIN=$minId MAX=$maxId");

        $objects = $repository->findSection($minId, $maxId, $limit);

        if (count($objects) == 0) {
            $this->logger->error("GET /api/people NOT FOUND");
            return new JsonResponse(['error' => 'People not found'], Response::HTTP_NOT_FOUND);
        }
        $this->logger->info('GET /api/people OK', ['objects' => $this->serializer->serialize($objects, 'json')]);
        return $this->json($objects, Response::HTTP_OK);
    }

    #[OA\Get(
        description: 'Returns a record of one person specified by id.',
        summary: 'Retrieves one person',
        tags: ['People'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the desired person',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Person retrieved successfully',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'John'),
                            new OA\Property(property: 'surname', type: 'string', example: 'Doe'),
                        ],
                        type: 'object'
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Person not found'
            )
        ]
    )]
    #[Route('/api/people/{id}', name: 'get_person_api', methods: ['GET'])]
    public function getPeople(EntityManagerInterface $manager, Request $request, int $id): Response
    {
        $repository = $manager->getRepository(Person::class);
        $this->logger->info("GET /api/people/$id");

        $person = $repository->find($id);
        if ($person === null) {
            $this->logger->error("GET /api/people/$id NOT FOUND");
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }

        $this->logger->info("GET /api/people/$id OK", ['person' => $this->serializer->serialize($person, 'json')]);
        return $this->json($person, Response::HTTP_OK);
    }

    #[OA\Delete(
        description: 'Deletes a person from the database using their ID.',
        summary: 'Deletes a person by ID',
        tags: ['People'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the person to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Person deleted successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Person not found'
            )
        ]
    )]
    #[Route('/api/people/{id}', name: 'delete_people_api', methods: ['DELETE'])]
    public function deletePerson(EntityManagerInterface $manager, int $id): Response
    {
        $this->logger->info("DELETE /api/people/$id");
        $person = $manager->getRepository(Person::class)->find($id);
        if ($person === null) {
            $this->logger->error("DELETE /api/people/$id NOT FOUND");
            return new JsonResponse(['error' => 'Person not found'], Response::HTTP_NOT_FOUND);
        }
        $manager->remove($person);
        $manager->flush();
        $this->logger->info('DELETE /api/people OK');
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


    #[OA\Put(
        description: 'Updates the details of an existing person in the database using their ID.',
        summary: 'Updates an existing person by ID',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John'),
                    new OA\Property(property: 'surname', type: 'string', example: 'Doe'),
                ],
                type: 'object'
            )
        ),
        tags: ['People'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID of the person to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Person updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'John'),
                        new OA\Property(property: 'surname', type: 'string', example: 'Doe'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid input'
            ),
            new OA\Response(
                response: 404,
                description: 'Person not found'
            )
        ]
    )]
    #[Route('/api/people/{id}', name: 'put_people_api', methods: ['PUT'])]
    public function putPerson(int $id, Request $request, EntityManagerInterface $manager, ValidatorInterface $validator): Response
    {
        $this->logger->info("PUT /api/people/$id", ['body' => $request->getContent()]);
        $repository = $manager->getRepository(Person::class);
        $existingPerson = $repository->find($id);
        if ($existingPerson === null) {
            $this->logger->error("PUT /api/people/$id NOT FOUND");
            return new JsonResponse(['error' => 'Person not found'], Response::HTTP_NOT_FOUND);
        }

        $newPerson = $this->serializer->deserialize($request->getContent(), Person::class, 'json');
        $errors = $validator->validate($newPerson);
        if (count($errors) > 0) {
            $this->logger->error("PUT /api/people/$id VALIDATION FAILED");
            return new JsonResponse(['error' => "Invalid input"], Response::HTTP_BAD_REQUEST);
        }

        $existingPerson->setName($newPerson->getName());
        $existingPerson->setSurname($newPerson->getSurname());

        $manager->flush();
        $this->logger->info("PUT /api/people/$id OK", ['person' => $this->serializer->serialize($existingPerson, 'json')]);
        return $this->json($existingPerson, Response::HTTP_OK);
    }
}
