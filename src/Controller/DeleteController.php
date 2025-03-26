<?php

namespace App\Controller;

use phpDocumentor\Reflection\Types\This;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DeleteController extends AbstractController{

    #[Route('/delete', name: 'delete', methods: ['GET'])]
    public function delete(): Response
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

        $api = "https://localhost:443/api/people/$id";
        try {
            $response = $httpClient->request('DELETE', $api, ['verify_peer' => false, 'verify_host' => false]);

            if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
                dd("Failed HTTP response code");
            }

        } catch (\Exception $e) {
            dd("Fetching exception");
    }
        return $this->redirectToRoute('error');
    }
}
