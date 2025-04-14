<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Person;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\DocBlock\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PeopleController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(Request $request, HttpClientInterface $httpClient, SerializerInterface $serializer): Response
    {
        $api = "https://localhost:443/api/people";

        try {
            $response = $httpClient->request('GET', $api, ['verify_peer' => false, 'verify_host' => false]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                dd($response->getContent() . $response->getStatusCode());
            }
        } catch (\Exception $e) {
            dd("Fetching exception");
        }

        $people = $serializer->deserialize($response->getContent(), 'App\Entity\Person[]', 'json');
        usort($people, function (Person $a, Person $b) {
            return $a->getId() <=> $b->getId();
        });

        return $this->render('index.html.twig', [
            'people' => $people,
        ]);
    }

    #[Route('/add', name: 'add', methods: ['GET'])]
    public function addPerson(): Response
    {
        return $this->render('add.html.twig');
    }

    #[Route('/add', name: 'add_request', methods: ['POST'])]
    public function addPersonRequest(Request $request, HttpClientInterface $httpClient): Response
    {
        $api = "https://localhost:443/api/people";

        $name = $request->request->get('name');
        $surname = $request->request->get('surname');

        try {
            $response = $httpClient->request('POST', $api, [
                'verify_peer' => false,
                'verify_host' => false,
                'headers' => ['Content-Type' => 'application/json'],
                'json' => ['name' => $name, 'surname' => $surname]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_CREATED) {
                dd($response->getContent() . $response->getStatusCode());
            }
        } catch (\Exception $e) {
            dd("Fetching exception");
        }

        return $this->redirectToRoute('index');
    }

    #[Route('/delete', name: 'delete', methods: ['GET'])]
    public function delete(?string $status): Response
    {
        return $this->render('delete.html.twig', []);
    }

    #[Route('/delete', name: 'delete_request', methods: ['POST'])]
    public function deleteRequest(Request $request, HttpClientInterface $httpClient): Response
    {
        $id = $request->request->get('id');
        if (!$id) {
            dd("Failed ID");
        }
        $id = (int) $id;
        return $this->executeDelete($id, $httpClient, 'delete');
    }

    #[Route('/delete/{id}', name: 'delete_get', methods: ['GET'])]
    public function deleteFromGet(int $id, HttpClientInterface $httpClient): Response
    {
        return $this->executeDelete($id, $httpClient, 'index');
    }

    private function executeDelete(int $id, HttpClientInterface $httpClient, string $routeToRedirect): Response
    {
        $api = "https://localhost:443/api/people/$id";
        try {
            $response = $httpClient->request('DELETE', $api, ['verify_peer' => false, 'verify_host' => false]);

            if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
                return $this->redirectToRoute($routeToRedirect, [
                    'status' => 'error',
                    'message' => 'Sorry, there was an error while processing your request.',
                ]);
            }

        } catch (\Exception $e) {
            return $this->redirectToRoute($routeToRedirect, [
                'status' => 'error',
                'message' => 'Sorry, there was an error while processing your request.',
            ]);
        }
        return $this->redirectToRoute($routeToRedirect, [
            'status' => 'success',
            'message' => 'The person with id ' . $id . ' has been deleted.'
        ]);
    }
}
