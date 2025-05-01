<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Enrollment;
use App\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EnrollmentController extends AbstractController
{
    #[Route('/my-enrollments', name: 'my_enrollments')]
    #[IsGranted('ROLE_USER')]
    public function myEnrollments(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get all user enrollments
        $enrollments = $entityManager->getRepository(Enrollment::class)->findBy(
            ['users' => $user],
            ['id' => 'DESC'] // Sort by newest first
        );

        // Group enrollments by upcoming and past
        $upcomingSessions = [];
        $pastSessions = [];
        $now = new \DateTime();

        foreach ($enrollments as $enrollment) {
            $session = $enrollment->getSession();
            if ($session->getDate() > $now) {
                $upcomingSessions[] = $session;
            } else {
                $pastSessions[] = $session;
            }
        }

        // Count enrollments by course
        $courseEnrollmentCounts = [];
        foreach ($enrollments as $enrollment) {
            $courseId = $enrollment->getSession()->getCourse()->getId();
            if (!isset($courseEnrollmentCounts[$courseId])) {
                $courseEnrollmentCounts[$courseId] = [
                    'course' => $enrollment->getSession()->getCourse(),
                    'count' => 0
                ];
            }
            $courseEnrollmentCounts[$courseId]['count']++;
        }

        return $this->render('enrollment/my_enrollments.html.twig', [
            'categories' => $categories,
            'upcomingSessions' => $upcomingSessions,
            'pastSessions' => $pastSessions,
            'courseEnrollmentCounts' => $courseEnrollmentCounts
        ]);
    }
    #[Route('/session/{id}/enroll', name: 'session_enroll')]
    #[IsGranted('ROLE_USER')]
    public function enroll(Session $session, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Check if user is already enrolled in this session
        $existingEnrollment = $entityManager->getRepository(Enrollment::class)->findOneBy([
            'users' => $user,
            'session' => $session
        ]);

        if ($existingEnrollment) {
            $this->addFlash('warning', 'You are already enrolled in this session.');
            return $this->redirectToRoute('course_show', ['id' => $session->getCourse()->getId()]);
        }

        // Check if session has available capacity
        $currentEnrollments = count($session->getEnrollments());
        if ($currentEnrollments >= $session->getCapacity()) {
            $this->addFlash('error', 'This session is already full.');
            return $this->redirectToRoute('course_show', ['id' => $session->getCourse()->getId()]);
        }

        // Check if session date is in the past
        if ($session->getDate() < new \DateTime()) {
            $this->addFlash('error', 'Cannot enroll in past sessions.');
            return $this->redirectToRoute('course_show', ['id' => $session->getCourse()->getId()]);
        }

        // Create new enrollment
        $enrollment = new Enrollment();
        $enrollment->setUsers($user);
        $enrollment->setSession($session);

        $entityManager->persist($enrollment);
        $entityManager->flush();

        $this->addFlash('success', 'You have successfully enrolled in this session.');
        return $this->redirectToRoute('course_show', ['id' => $session->getCourse()->getId()]);
    }

    #[Route('/session/{id}/unenroll', name: 'session_unenroll')]
    #[IsGranted('ROLE_USER')]
    public function unenroll(Session $session, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Find the enrollment
        $enrollment = $entityManager->getRepository(Enrollment::class)->findOneBy([
            'users' => $user,
            'session' => $session
        ]);

        if (!$enrollment) {
            $this->addFlash('warning', 'You are not enrolled in this session.');
            return $this->redirectToRoute('course_show', ['id' => $session->getCourse()->getId()]);
        }

        // Check if session date is in the past
        if ($session->getDate() < new \DateTime()) {
            $this->addFlash('error', 'Cannot unenroll from past sessions.');
            return $this->redirectToRoute('course_show', ['id' => $session->getCourse()->getId()]);
        }

        $entityManager->remove($enrollment);
        $entityManager->flush();

        $this->addFlash('success', 'You have successfully unenrolled from this session.');
        return $this->redirectToRoute('course_show', ['id' => $session->getCourse()->getId()]);
    }
}
