<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteController extends AbstractController{
    #[Route('/delete', name: 'delete')]
    public function delete(): Response
    {
        return $this->render('delete.html.twig', []);
    }

    #[Route('/delete', name: 'delete_request', methods: ['POST'])]
    public function deleteRequest(Request $request): Response
    {
        $id = $request->request->get('id');
        if (!$id) {
            $this->addFlash('error', 'Invalid ID');
            return $this->redirectToRoute('delete');
        }

        $api = 'http://localhost:80/api/people/{$id}';

        return $this->redirectToRoute('delete');
    }
}
