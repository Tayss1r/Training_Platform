<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CourseController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $courses = $entityManager->getRepository(Course::class)->findAll();

        return $this->render('course/index.html.twig', [
            'categories' => $categories,
            'courses' => $courses,
        ]);
    }

    #[Route('/course/{id}', name: 'course_show')]
    public function show(Course $course, EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Calculate remaining capacity for each session
        $sessionsCapacity = [];
        foreach ($course->getSessions() as $session) {
            $currentEnrollments = count($session->getEnrollments());
            $remainingCapacity = $session->getCapacity() - $currentEnrollments;
            $sessionsCapacity[$session->getId()] = $remainingCapacity;
        }

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'categories' => $categories,
            'sessionsCapacity' => $sessionsCapacity,
        ]);
    }

    #[Route('/course/{id}/syllabus', name: 'course_download_syllabus')]
    public function downloadSyllabus(Course $course): Response
    {
        // Get the syllabus filename stored for the course
        $syllabusFilename = $course->getSyllabus();

        if (!$syllabusFilename) {
            $this->addFlash('error', 'No syllabus uploaded for this course.');
            return $this->redirectToRoute('course_show', ['id' => $course->getId()]);
        }

        // Construct the full path to the syllabus file
        $syllabusPath = $this->getParameter('kernel.project_dir') . '/public/uploads/syllabuses/' . $syllabusFilename;

        // Check if the file exists
        if (!file_exists($syllabusPath)) {
            $this->addFlash('error', 'Syllabus file not found.');
            return $this->redirectToRoute('course_show', ['id' => $course->getId()]);
        }

        // Return the file for download
        return (new BinaryFileResponse($syllabusPath))
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'syllabus_' . $course->getId() . '.pdf');
    }
}
