<?php

namespace App\Controller;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class CategoryController extends AbstractController
{
    #[Route('/category/{id}', name: 'category_courses')]
    public function show(Category $category, EntityManagerInterface $entityManager, Request $request): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $allCourses = $category->getCourses();
        $sort = $request->query->get('sort', 'title_asc');
        $allCoursesArr = $allCourses->toArray();
        if ($sort === 'title_asc') {
            usort($allCoursesArr, fn($a, $b) => strcmp($a->getTitle(), $b->getTitle()));
        } elseif ($sort === 'title_desc') {
            usort($allCoursesArr, fn($a, $b) => strcmp($b->getTitle(), $a->getTitle()));
        } elseif ($sort === 'duration_asc') {
            usort($allCoursesArr, fn($a, $b) => $a->getDuration() <=> $b->getDuration());
        } elseif ($sort === 'duration_desc') {
            usort($allCoursesArr, fn($a, $b) => $b->getDuration() <=> $a->getDuration());
        }
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 9;
        $offset = ($page - 1) * $limit;
        $totalCourses = count($allCoursesArr);
        $totalPages = (int)ceil($totalCourses / $limit);
        $courses = array_slice($allCoursesArr, $offset, $limit);

        return $this->render('category/courses.html.twig', [
            'category' => $category,
            'courses' => $courses,
            'categories' => $categories,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCourses' => $totalCourses,
            'selectedSort' => $sort,
        ]);
    }
} 