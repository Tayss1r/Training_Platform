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

        $sort = $request->query->get('sort', 'title_asc');
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 9;
        $offset = ($page - 1) * $limit;

        if (empty($query)) {
            $allCourses = $entityManager->getRepository(Course::class)->findAll();
        } else {
            $allCourses = $entityManager->getRepository(Course::class)
                ->createQueryBuilder('c')
                ->where('c.title LIKE :query')
                ->orWhere('c.description LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->getQuery()
                ->getResult();
        }

        if ($sort === 'title_asc') {
            usort($allCourses, fn($a, $b) => strcmp($a->getTitle(), $b->getTitle()));
        } elseif ($sort === 'title_desc') {
            usort($allCourses, fn($a, $b) => strcmp($b->getTitle(), $a->getTitle()));
        } elseif ($sort === 'duration_asc') {
            usort($allCourses, fn($a, $b) => $a->getDuration() <=> $b->getDuration());
        } elseif ($sort === 'duration_desc') {
            usort($allCourses, fn($a, $b) => $b->getDuration() <=> $a->getDuration());
        }
        $totalCourses = count($allCourses);
        $totalPages = (int)ceil($totalCourses / $limit);
        $courses = array_slice($allCourses, $offset, $limit);

        return $this->render('search/results.html.twig', [
            'query' => $query,
            'courses' => $courses,
            'categories' => $categories,
            'selectedSort' => $sort,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCourses' => $totalCourses,
        ]);
    }
} 