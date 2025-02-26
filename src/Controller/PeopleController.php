<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Person;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PeopleController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(Request $request, EntityManagerInterface $manager): Response
    {
        if ($request->isMethod('POST')) {
            $person = new Person();
            $name = $request->request->get('name');
            $surname = $request->request->get('surname');
            $person->setName($name);
            $person->setSurname($surname);
            $manager->persist($person);
            $manager->flush();
        }

        $people = $manager->getRepository(Person::class)->findAll();

        return $this->render('index.html.twig', [
            'people' => $people,
        ]);
    }

    #[Route('/delete/{id}', name: 'delete_person')]
    public function delete_person(EntityManagerInterface $manager, int $id): Response
    {
        $person = $manager->getRepository(Person::class)->find($id);
        $manager->remove($person);
        $manager->flush();
        return $this->redirectToRoute('index');
    }
}
