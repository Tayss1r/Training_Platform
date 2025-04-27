<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Session;
use App\Entity\Category;
use App\Form\CourseType;
use App\Form\SessionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $courses = $entityManager->getRepository(Course::class)->findAll();
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $sessions = $entityManager->getRepository(Session::class)->findAll();

        return $this->render('admin/index.html.twig', [
            'courses' => $courses,
            'categories' => $categories,
            'sessions' => $sessions
        ]);
    }

    #[Route('/admin/sessions', name: 'admin_sessions')]
    public function sessions(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $courses = $entityManager->getRepository(Course::class)->findAll();

        $selectedCategoryId = $request->query->get('category');
        $selectedCourseId = $request->query->get('course');
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $filteredCourses = $courses;
        if ($selectedCategoryId) {
            $filteredCourses = $entityManager->getRepository(Course::class)->findBy(['category' => $selectedCategoryId]);
        }

        $sessions = [];
        $totalSessions = 0;
        if ($selectedCourseId) {
            $qb = $entityManager->getRepository(Session::class)->createQueryBuilder('s')
                ->where('s.course = :course')
                ->setParameter('course', $selectedCourseId);
        } elseif ($selectedCategoryId) {
            $qb = $entityManager->getRepository(Session::class)->createQueryBuilder('s')
                ->join('s.course', 'c')
                ->where('c.category = :cat')
                ->setParameter('cat', $selectedCategoryId);
        } else {
            $qb = $entityManager->getRepository(Session::class)->createQueryBuilder('s');
        }
        $totalSessions = count($qb->getQuery()->getResult());
        $sessions = $qb->setFirstResult($offset)->setMaxResults($limit)->getQuery()->getResult();

        $totalPages = (int)ceil($totalSessions / $limit);

        return $this->render('admin/sessions.html.twig', [
            'sessions' => $sessions,
            'categories' => $categories,
            'courses' => $filteredCourses,
            'selectedCategoryId' => $selectedCategoryId,
            'selectedCourseId' => $selectedCourseId,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/admin/sessions/new', name: 'admin_session_new')]
    public function newSession(Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = new Session();
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($session);
            $entityManager->flush();

            $this->addFlash('success', 'Session created successfully.');
            return $this->redirectToRoute('admin_sessions');
        }

        return $this->render('admin/session_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'New Session'
        ]);
    }

    #[Route('/admin/sessions/{id}/edit', name: 'admin_session_edit')]
    public function editSession(Request $request, Session $session, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Session updated successfully.');
            return $this->redirectToRoute('admin_sessions');
        }

        return $this->render('admin/session_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edit Session'
        ]);
    }

    #[Route('/admin/sessions/{id}/delete', name: 'admin_session_delete', methods: ['POST'])]
    public function deleteSession(Session $session, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($session);
        $entityManager->flush();

        $this->addFlash('success', 'Session deleted successfully.');
        return $this->redirectToRoute('admin_sessions');
    }

    #[Route('/admin/courses', name: 'admin_courses')]
    public function courses(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $courses = $entityManager->getRepository(Course::class)->findAll();

        $selectedCategoryId = $request->query->get('category');
        $selectedCourseId = $request->query->get('course');
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $filteredCourses = $courses;
        if ($selectedCategoryId) {
            $filteredCourses = $entityManager->getRepository(Course::class)->findBy(['category' => $selectedCategoryId]);
        }

        $sessions = [];
        $totalSessions = 0;
        if ($selectedCourseId) {
            $qb = $entityManager->getRepository(Session::class)->createQueryBuilder('s')
                ->where('s.course = :course')
                ->setParameter('course', $selectedCourseId);
        } elseif ($selectedCategoryId) {
            $qb = $entityManager->getRepository(Session::class)->createQueryBuilder('s')
                ->join('s.course', 'c')
                ->where('c.category = :cat')
                ->setParameter('cat', $selectedCategoryId);
        } else {
            $qb = $entityManager->getRepository(Session::class)->createQueryBuilder('s');
        }
        $totalSessions = count($qb->getQuery()->getResult());
        $sessions = $qb->setFirstResult($offset)->setMaxResults($limit)->getQuery()->getResult();

        $totalPages = (int)ceil($totalSessions / $limit);

        return $this->render('admin/courses.html.twig', [
            'sessions' => $sessions,
            'categories' => $categories,
            'courses' => $filteredCourses,
            'selectedCategoryId' => $selectedCategoryId,
            'selectedCourseId' => $selectedCourseId,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }
    #[Route('/admin/courses/new', name: 'admin_course_new')]
    public function newCourse(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $directory = $this->getParameter('imageDirectory');
                    $photoFile->move($directory, $newFilename);

                    $course->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error while uploading : ' . $e->getMessage());
                    return $this->redirectToRoute('admin_course_edit', ['id' => $course->getId()]);
                }
            }
            $entityManager->persist($course);
            $entityManager->flush();

            $this->addFlash('success', 'Course created successfully.');
            return $this->redirectToRoute('admin_courses');
        }

        return $this->render('admin/course_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'New Course'
        ]);
    }

    #[Route('/admin/courses/{id}/edit', name: 'admin_course_edit')]
    public function editCourse(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Course updated successfully.');
            return $this->redirectToRoute('admin_courses');
        }

        return $this->render('admin/course_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edit Course'
        ]);
    }

    #[Route('/admin/courses/{id}/delete', name: 'admin_course_delete', methods: ['POST'])]
    public function deleteCourse(Course $course, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($course);
        $entityManager->flush();

        $this->addFlash('success', 'Course deleted successfully.');
        return $this->redirectToRoute('admin_courses');
    }

} 