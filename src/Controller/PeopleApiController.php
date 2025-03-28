<?php

namespace App\Controller;

use App\Entity\Person;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PeopleApiController extends AbstractController
{
    #[Route('/api/people', name: 'post_people_api', methods: ['POST'])]
    public function postPerson(Request $request, EntityManagerInterface $manager, SerializerInterface $serializer, ValidatorInterface $validator): Response
    {
        $person = $serializer->deserialize($request->getContent(), Person::class, 'json');

        $errors = $validator->validate($person);
        if (count($errors) > 0) {
            return new Response("Invalid input", Response::HTTP_BAD_REQUEST);
        }

        $manager->persist($person);
        $manager->flush();
        return $this->json($person, Response::HTTP_CREATED);
    }

    #[Route('/api/people/{id?}', name: 'get_people_api', methods: ['GET'])]
    public function getPerson(EntityManagerInterface $manager, Request $request, ?int $id): Response
    {
        $repository = $manager->getRepository(Person::class);

        if ($id === null) {
            $limit = $request->query->getInt('limit');
            $minId = $request->query->getInt('min_id');
            $maxId = $request->query->getInt('max_id');
        } else {
            $limit = 1;
            $minId = $id;
            $maxId = $id;
        }

        $objects = $repository->findSection($minId, $maxId, $limit);

        if (count($objects) == 0) {
            return new JsonResponse(['error' => 'People not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($objects, Response::HTTP_OK);
    }

    #[Route('/api/people/{id}', name: 'delete_people_api', methods: ['DELETE'])]
    public function deletePerson(EntityManagerInterface $manager, int $id): Response
    {
        $person = $manager->getRepository(Person::class)->find($id);
        if ($person === null) {
            return new JsonResponse(['error' => 'Person not found'], Response::HTTP_NOT_FOUND);
        }
        $manager->remove($person);
        $manager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
