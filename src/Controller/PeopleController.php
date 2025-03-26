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

        if ($request->isMethod('POST')) {
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

        try {
            $response = $httpClient->request('GET', $api, ['verify_peer' => false, 'verify_host' => false]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                dd($response->getContent() . $response->getStatusCode());
            }
        } catch (\Exception $e) {
            dd("Fetching exception");
        }

        $people = $serializer->deserialize($response->getContent(), 'App\Entity\Person[]', 'json');

        return $this->render('index.html.twig', [
            'people' => $people,
        ]);
    }
}
