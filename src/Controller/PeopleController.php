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
                $this->addFlash('status', 'error');
                $this->addFlash('message', $response->getStatusCode() .  ' Sorry, there was an error while processing your request.' );
                return $this->redirectToRoute('add');
            }
        } catch (\Exception $e) {
            $this->addFlash('status', 'error');
            $this->addFlash('message', 'Sorry, there was an error while processing your request.' );
            return $this->redirectToRoute('add');
        }

        $this->addFlash('status', 'success');
        $this->addFlash('message', 'The person was successfully added.' );
        return $this->redirectToRoute('add');
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
            $this->addFlash('status', 'error');
            $this->addFlash('message', 'Sorry, there was an error while processing your request.' );
            $this->redirectToRoute('delete');
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
                $this->addFlash('status', 'error');
                $this->addFlash('message', $response->getStatusCode() .  ' Sorry, there was an error while processing your request.' );
                return $this->redirectToRoute($routeToRedirect);
            }

        } catch (\Exception $e) {
            $this->addFlash('status', 'error');
            $this->addFlash('message', $response->getStatusCode() .  ' Sorry, there was an error while processing your request.' );
            return $this->redirectToRoute($routeToRedirect);
        }
        $this->addFlash('status', 'success');
        $this->addFlash('message', 'The person with id ' . $id . ' was successfully deleted.' );
        return $this->redirectToRoute($routeToRedirect);
    }

    #[Route('/edit/{id}', name: 'edit', defaults: ['id' => null], methods: ['GET'])]
    public function editPerson(?int $id): Response
    {
        return $this->render('edit.html.twig', [
            'id' => $id,
        ]);
    }

    #[Route('/edit', name: 'edit_request', methods: ['POST'])]
    public function editPersonRequest(Request $request, HttpClientInterface $httpClient): Response
    {
        $id = $request->request->get('id');
        $name = $request->request->get('name');
        $surname = $request->request->get('surname');

        $api = "https://localhost:443/api/people/$id";

        try {
            $response = $httpClient->request('PUT', $api, [
                'verify_peer' => false,
                'verify_host' => false,
                'headers' => ['Content-Type' => 'application/json'],
                'json' => ['name' => $name, 'surname' => $surname]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->addFlash('status', 'error');
                $this->addFlash('message', $response->getStatusCode() .  ' Sorry, there was an error while processing your request.' );
            }
        } catch (\Exception $e) {
            $this->addFlash('status', 'error');
            $this->addFlash('message', 'Sorry, there was an error while processing your request.' );
        }

        $this->addFlash('status', 'success');
        $this->addFlash('message', 'The person with id ' . $id . ' was successfully edited.' );
        return $this->redirectToRoute('edit');
    }
}
