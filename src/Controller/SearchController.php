<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function search(Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $request->query->get('q', '');
        $categories = $entityManager->getRepository(Category::class)->findAll();

        if (empty($query)) {
            $courses = $entityManager->getRepository(Course::class)->findAll();
        } else {
            $courses = $entityManager->getRepository(Course::class)
                ->createQueryBuilder('c')
                ->where('c.title LIKE :query')
                ->orWhere('c.description LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->getQuery()
                ->getResult();
        }

        return $this->render('search/results.html.twig', [
            'query' => $query,
            'courses' => $courses,
            'categories' => $categories,
        ]);
    }
} 