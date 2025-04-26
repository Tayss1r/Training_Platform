<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Category;
use App\Form\CourseType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Psr\Log\LoggerInterface;

class CourseController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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

    #[Route('/course/{id}', name: 'course_show', priority: 1)]
    public function show(Course $course, EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'categories' => $categories,
        ]);
    }

    #[Route('/course/{id}/syllabus', name: 'course_download_syllabus')]
    public function downloadSyllabus(Course $course): Response
    {
        // For demo, assume syllabus is stored as 'public/syllabus/course_{id}.pdf'
        $syllabusPath = $this->getParameter('kernel.project_dir') . '/public/syllabus/course_' . $course->getId() . '.pdf';

        if (!file_exists($syllabusPath)) {
            $this->addFlash('error', 'Syllabus not found.');
            return $this->redirectToRoute('course_show', ['id' => $course->getId()]);
        }

        return (new BinaryFileResponse($syllabusPath))
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'syllabus_' . $course->getId() . '.pdf');
    }

    #[Route('/course/new', name: 'course_new', priority: 2)]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->logger->info('Accessing course_new route');

        // Check if user is admin or instructor
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_INSTRUCTOR')) {
            $this->logger->warning('Non-admin and non-instructor user attempted to access course_new route');
            throw $this->createAccessDeniedException('Only administrators and instructors can create courses.');
        }

        try {
            $course = new Course();
            $course->setCreatedAt(new \DateTime());
            $course->setLastUpdatedAt(new \DateTime());

            // Get the current user
            $currentUser = $this->getUser();
            if ($currentUser) {
                $course->setInstructor($currentUser);
            }

            $form = $this->createForm(CourseType::class, $course);
            $form->handleRequest($request);

            $this->logger->info('Form submitted: ' . ($form->isSubmitted() ? 'true' : 'false'));

            if ($form->isSubmitted()) {
                $this->logger->info('Form valid: ' . ($form->isValid() ? 'true' : 'false'));

                if ($form->isValid()) {
                    $this->logger->info('Processing form submission');
                    $imageFile = $form->get('image')->getData();

                    if ($imageFile) {
                        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                        try {
                            $imageFile->move(
                                $this->getParameter('course_images_directory'),
                                $newFilename
                            );
                        } catch (FileException $e) {
                            $this->logger->error('File upload error: ' . $e->getMessage());
                        }

                        $course->setImage($newFilename);
                    }

                    $entityManager->persist($course);
                    $entityManager->flush();

                    $this->addFlash('success', 'Course created successfully!');
                    return $this->redirectToRoute('home');
                }
            }

            // Add debug information
            $this->logger->info('Rendering new course form');
            $this->logger->info('Current URL: ' . $request->getUri());
            $this->logger->info('User roles: ' . implode(', ', $this->getUser()->getRoles()));

            return $this->render('course/new.html.twig', [
                'form' => $form->createView(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error in course_new: ' . $e->getMessage());
            $this->addFlash('error', 'An error occurred while creating the course. Please try again.');
            return $this->redirectToRoute('home');
        }
    }

    #[Route('/course/{id}/delete', name: 'course_delete', methods: ['DELETE'])]
    public function delete(Course $course, EntityManagerInterface $entityManager): Response
    {
        $this->logger->info('Attempting to delete course: ' . $course->getId());

        // Check if user is admin or the instructor of this course
        if (
            !$this->isGranted('ROLE_ADMIN') &&
            (!$this->isGranted('ROLE_INSTRUCTOR') || $this->getUser()->getUserIdentifier() !== $course->getInstructor()->getUserIdentifier())
        ) {
            $this->logger->warning('Unauthorized attempt to delete course: ' . $course->getId());
            throw $this->createAccessDeniedException('You are not authorized to delete this course.');
        }

        try {
            // Remove all sessions associated with this course
            foreach ($course->getSessions() as $session) {
                $entityManager->remove($session);
            }

            // Remove the course
            $entityManager->remove($course);
            $entityManager->flush();

            $this->logger->info('Course deleted successfully: ' . $course->getId());
            return new Response(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting course: ' . $e->getMessage());
            return new Response('Error deleting course', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/course/{id}/edit', name: 'course_edit')]
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Only admins or the instructor can edit
        if (!$this->isGranted('ROLE_ADMIN') && (!($this->isGranted('ROLE_INSTRUCTOR') && $this->getUser() && $this->getUser()->getUserIdentifier() === $course->getInstructor()->getUserIdentifier()))) {
            throw $this->createAccessDeniedException('You are not authorized to edit this course.');
        }

        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('course_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'File upload error: ' . $e->getMessage());
                }
                $course->setImage($newFilename);
            }
            $course->setLastUpdatedAt(new \DateTime());
            $entityManager->flush();
            $this->addFlash('success', 'Course updated successfully!');
            return $this->redirectToRoute('course_show', ['id' => $course->getId()]);
        }
        return $this->render('course/edit.html.twig', [
            'form' => $form->createView(),
            'course' => $course,
        ]);
    }
}
