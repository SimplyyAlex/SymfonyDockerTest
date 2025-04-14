<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;


final class AddController extends AbstractController
{
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
}
