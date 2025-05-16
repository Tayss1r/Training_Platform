<?php

namespace App\Controller;

use App\Entity\Course;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class HomeController extends BaseController
{
    #[Route('/', name: 'home')]
    public function index(Request $request): Response
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 9;
        $offset = ($page - 1) * $limit;

        $sort = $request->query->get('sort', 'title_asc');
        $qb = $this->entityManager->getRepository(Course::class)->createQueryBuilder('c');
        $allCourses = $qb->getQuery()->getResult();
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

        // Categories are now included via the BaseController's getCommonData method

        return $this->render('home/index.html.twig', [
            'courses' => $courses,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCourses' => $totalCourses,
            'selectedSort' => $sort,
        ]);
    }
}
