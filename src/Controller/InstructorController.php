<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Session;
use App\Entity\Category;
use App\Entity\Material;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_INSTRUCTOR')]
#[Route('/instructor')]
class InstructorController extends AbstractController
{
    #[Route('/', name: 'instructor_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get instructor's courses
        // In a real application, we would have a relationship between instructors and courses
        // For now, we'll get all courses as a demonstration
        $courses = $entityManager->getRepository(Course::class)->findAll();

        // Get instructor's sessions
        // In a real application, we would filter sessions by instructor
        // For now, we'll get all sessions as a demonstration
        $sessions = $entityManager->getRepository(Session::class)->findAll();
        $sessionsCount = count($sessions);

        // Get upcoming sessions (next 7 days)
        $now = new \DateTime();
        $oneWeekLater = (new \DateTime())->modify('+7 days');

        $upcomingSessions = [];
        foreach ($sessions as $session) {
            $sessionDate = $session->getDate();
            if ($sessionDate > $now && $sessionDate <= $oneWeekLater) {
                $upcomingSessions[] = $session;
            }
        }

        // Get students count (users with enrollments in instructor's sessions)
        $studentIds = [];
        $enrollmentsCount = 0;

        foreach ($sessions as $session) {
            $enrollments = $session->getEnrollments();
            $enrollmentsCount += count($enrollments);

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->getUsers();
                if ($student) {
                    $studentIds[$student->getId()] = true;
                }
            }
        }

        $studentsCount = count($studentIds);

        return $this->render('instructor/dashboard.html.twig', [
            'categories' => $categories,
            'courses' => $courses,
            'sessions' => $sessions,
            'upcomingSessions' => $upcomingSessions,
            'sessionsCount' => $sessionsCount,
            'studentsCount' => $studentsCount,
            'enrollmentsCount' => $enrollmentsCount,
        ]);
    }

    #[Route('/sessions', name: 'instructor_sessions')]
    public function sessions(EntityManagerInterface $entityManager): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get all courses for filtering
        $courses = $entityManager->getRepository(Course::class)->findAll();

        // Get all sessions
        // In a real application, we would filter sessions by instructor
        $sessions = $entityManager->getRepository(Session::class)->findAll();

        // Get current date for comparison
        $now = new \DateTime();

        // Categorize sessions
        $upcomingSessions = [];
        $inProgressSessions = [];
        $completedSessions = [];

        foreach ($sessions as $session) {
            $startDate = $session->getStartDate();
            $endDate = $session->getEndDate();

            if ($startDate > $now) {
                $upcomingSessions[] = $session;
            } elseif ($endDate < $now) {
                $completedSessions[] = $session;
            } else {
                $inProgressSessions[] = $session;
            }
        }

        // Get enrollment counts for each session
        $sessionEnrollmentCounts = [];
        foreach ($sessions as $session) {
            $sessionEnrollmentCounts[$session->getId()] = count($session->getEnrollments());
        }

        return $this->render('instructor/sessions.html.twig', [
            'categories' => $categories,
            'courses' => $courses,
            'sessions' => $sessions,
            'upcomingSessions' => $upcomingSessions,
            'inProgressSessions' => $inProgressSessions,
            'completedSessions' => $completedSessions,
            'sessionEnrollmentCounts' => $sessionEnrollmentCounts,
        ]);
    }

    #[Route('/students', name: 'instructor_students')]
    public function students(EntityManagerInterface $entityManager): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get all courses for filtering
        $courses = $entityManager->getRepository(Course::class)->findAll();

        // Get all sessions
        // In a real application, we would filter sessions by instructor
        $sessions = $entityManager->getRepository(Session::class)->findAll();

        // Get all enrollments for these sessions
        $enrollments = [];
        foreach ($sessions as $session) {
            foreach ($session->getEnrollments() as $enrollment) {
                $enrollments[] = $enrollment;
            }
        }

        // Get unique students
        $students = [];
        foreach ($enrollments as $enrollment) {
            $student = $enrollment->getUsers();
            if ($student && !isset($students[$student->getId()])) {
                $students[$student->getId()] = [
                    'user' => $student,
                    'enrollments' => [],
                    'sessions' => [],
                    'courses' => []
                ];
            }

            if ($student) {
                $students[$student->getId()]['enrollments'][] = $enrollment;
                $session = $enrollment->getSession();
                if ($session) {
                    $students[$student->getId()]['sessions'][$session->getId()] = $session;
                    $course = $session->getCourse();
                    if ($course) {
                        $students[$student->getId()]['courses'][$course->getId()] = $course;
                    }
                }
            }
        }

        // Calculate additional statistics for each student
        foreach ($students as &$studentData) {
            $studentData['sessionsCount'] = count($studentData['sessions']);
            $studentData['coursesCount'] = count($studentData['courses']);
            $studentData['enrollmentsCount'] = count($studentData['enrollments']);
        }

        return $this->render('instructor/students.html.twig', [
            'categories' => $categories,
            'courses' => $courses,
            'students' => $students,
        ]);
    }

    #[Route('/analytics', name: 'instructor_analytics')]
    public function analytics(EntityManagerInterface $entityManager): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get all courses
        $courses = $entityManager->getRepository(Course::class)->findAll();

        // Get all sessions
        // In a real application, we would filter sessions by instructor
        $sessions = $entityManager->getRepository(Session::class)->findAll();

        // Calculate enrollment trends (by month)
        $enrollmentTrends = [];
        $coursePopularity = [];

        // Initialize course popularity data
        foreach ($courses as $course) {
            $coursePopularity[$course->getId()] = [
                'course' => $course,
                'count' => 0
            ];
        }

        // Process enrollments
        foreach ($sessions as $session) {
            $courseId = $session->getCourse()->getId();
            $enrollments = $session->getEnrollments();

            // Add to course popularity
            $coursePopularity[$courseId]['count'] += count($enrollments);

            // Process enrollment trends
            foreach ($enrollments as $enrollment) {
                $createdAt = $enrollment->getCreatedAt();
                if ($createdAt) {
                    $month = $createdAt->format('Y-m');
                    if (!isset($enrollmentTrends[$month])) {
                        $enrollmentTrends[$month] = 0;
                    }
                    $enrollmentTrends[$month]++;
                }
            }
        }

        // Sort enrollment trends by month
        ksort($enrollmentTrends);

        // Prepare data for charts
        $enrollmentTrendsLabels = array_keys($enrollmentTrends);
        $enrollmentTrendsData = array_values($enrollmentTrends);

        $courseLabels = [];
        $courseData = [];
        foreach ($coursePopularity as $data) {
            $courseLabels[] = $data['course']->getTitle();
            $courseData[] = $data['count'];
        }

        return $this->render('instructor/analytics.html.twig', [
            'categories' => $categories,
            'courses' => $courses,
            'enrollmentTrendsLabels' => json_encode($enrollmentTrendsLabels),
            'enrollmentTrendsData' => json_encode($enrollmentTrendsData),
            'courseLabels' => json_encode($courseLabels),
            'courseData' => json_encode($courseData),
        ]);
    }

    #[Route('/sessions/{id}', name: 'instructor_session_details', priority: -10)]
    public function sessionDetails(EntityManagerInterface $entityManager, int $id): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get the session
        $session = $entityManager->getRepository(Session::class)->find($id);

        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        // Get enrollments for this session
        $enrollments = $session->getEnrollments();

        // Get course details
        $course = $session->getCourse();

        // Get course materials from the database
        $materials = $entityManager->getRepository(Material::class)->findByCourse($course->getId());

        // Filter to only include document type materials
        $documents = array_filter($materials, function ($material) {
            return $material->getType() === 'document';
        });

        $sessionMaterials = [
            'documents' => $documents
        ];

        return $this->render('instructor/session_details.html.twig', [
            'categories' => $categories,
            'session' => $session,
            'course' => $course,
            'enrollments' => $enrollments,
            'sessionMaterials' => $sessionMaterials
        ]);
    }

    #[Route('/sessions/{id}/students', name: 'instructor_session_students', priority: -10)]
    public function sessionStudents(EntityManagerInterface $entityManager, int $id): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get the session
        $session = $entityManager->getRepository(Session::class)->find($id);

        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        // Get enrollments and students for this session
        $enrollments = $session->getEnrollments();
        $students = [];

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->getUsers();
            if ($student) {
                $students[] = [
                    'user' => $student,
                    'enrollment' => $enrollment
                ];
            }
        }

        return $this->render('instructor/session_students.html.twig', [
            'categories' => $categories,
            'session' => $session,
            'students' => $students,
            'course' => $session->getCourse()
        ]);
    }

    #[Route('/sessions/{id}/progress', name: 'instructor_session_progress', priority: -10)]
    public function sessionProgress(EntityManagerInterface $entityManager, int $id): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get the session
        $session = $entityManager->getRepository(Session::class)->find($id);

        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        // Get enrollments for this session
        $enrollments = $session->getEnrollments();

        // Generate session data
        $progressData = [
            'attendance' => [
                'attended' => count($enrollments) * 0.85,
                'total' => count($enrollments)
            ],
            'satisfaction' => [
                'rating' => 4.2,
                'responses' => count($enrollments) * 0.6
            ]
        ];

        return $this->render('instructor/session_progress.html.twig', [
            'categories' => $categories,
            'session' => $session,
            'course' => $session->getCourse(),
            'progressData' => $progressData
        ]);
    }

    #[Route('/sessions/test', name: 'instructor_session_test', methods: ['GET'], priority: 10)]
    public function testSession(): Response
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'Test route is working'
        ]);
    }

    #[Route('/sessions/new', name: 'instructor_session_create', methods: ['POST'], priority: 10)]
    public function createSession(EntityManagerInterface $entityManager, Request $request): Response
    {
        // Enable detailed error reporting for debugging
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        // Get form data
        $courseId = $request->request->get('course');
        $location = $request->request->get('location');
        $startDate = $request->request->get('startDate');
        $endDate = $request->request->get('endDate');
        $time = $request->request->get('time');
        $capacity = $request->request->get('capacity');
        $sessionType = $request->request->get('sessionType');
        $description = $request->request->get('description');

        // Validate required fields
        $missingFields = [];
        if (!$courseId) $missingFields[] = 'Course';
        if (!$location) $missingFields[] = 'Location';
        if (!$startDate) $missingFields[] = 'Start Date';
        if (!$endDate) $missingFields[] = 'End Date';
        if (!$time) $missingFields[] = 'Time';
        if (!$capacity) $missingFields[] = 'Capacity';

        if (!empty($missingFields)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Please fill out the following required fields: ' . implode(', ', $missingFields)
            ], 400);
        }

        // Validate that start date is not after end date
        try {
            $startDateObj = new \DateTime($startDate);
            $endDateObj = new \DateTime($endDate);

            if ($startDateObj > $endDateObj) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Start date cannot be after end date'
                ], 400);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid date format: ' . $e->getMessage()
            ], 400);
        }

        try {
            // Log request data for debugging
            error_log('Creating session with data: ' . json_encode([
                'courseId' => $courseId,
                'location' => $location,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'time' => $time,
                'capacity' => $capacity,
                'sessionType' => $sessionType
            ]));

            // Get the course
            $course = $entityManager->getRepository(Course::class)->find($courseId);
            if (!$course) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Create new session
            $session = new Session();
            $session->setCourse($course);
            $session->setLocation($location);

            try {
                // Parse the time (dates were already parsed during validation)
                $timeObj = new \DateTime($time);

                error_log('Parsed dates: ' . json_encode([
                    'startDate' => $startDateObj->format('Y-m-d'),
                    'endDate' => $endDateObj->format('Y-m-d'),
                    'time' => $timeObj->format('H:i:s')
                ]));

                $session->setStartDate($startDateObj);
                $session->setEndDate($endDateObj);
                $session->setTime($timeObj);
            } catch (\Exception $e) {
                error_log('Time parsing error: ' . $e->getMessage());
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid time format: ' . $e->getMessage(),
                    'time' => $time
                ], 400);
            }

            $session->setCapacity((int)$capacity);
            $session->setType($sessionType);
            $session->setDescription($description);
            $session->setCreatedAt(new \DateTime());

            error_log('Session object created, about to persist');

            // Save to database
            $entityManager->persist($session);
            $entityManager->flush();

            error_log('Session saved with ID: ' . $session->getId());

            // Prepare session data for response
            $sessionData = [
                'id' => $session->getId(),
                'course' => $course->getTitle(),
                'date' => $session->getStartDate()->format('Y-m-d'),
                'time' => $session->getTime()->format('H:i'),
                'capacity' => $session->getCapacity(),
                'enrolled' => 0,
                'status' => 'upcoming',
                'statusLabel' => 'Upcoming',
                'statusClass' => 'badge-success'
            ];

            return new JsonResponse([
                'success' => true,
                'message' => sprintf(
                    'Session for "%s" has been created successfully. It will take place on %s at %s.',
                    $course->getTitle(),
                    $session->getStartDate()->format('F j, Y'),
                    $session->getTime()->format('g:i A')
                ),
                'session' => $sessionData
            ]);
        } catch (\Exception $e) {
            // Log the error
            error_log('Error creating session: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            // Return detailed error information
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    #[Route('/sessions/{id}/edit', name: 'instructor_session_edit', methods: ['POST'], priority: -10)]
    public function editSession(EntityManagerInterface $entityManager, Request $request, int $id): Response
    {
        // Get the session
        $session = $entityManager->getRepository(Session::class)->find($id);

        if (!$session) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        // Get form data
        $courseId = $request->request->get('course');
        $location = $request->request->get('location');
        $startDate = $request->request->get('startDate');
        $endDate = $request->request->get('endDate');
        $time = $request->request->get('time');
        $capacity = $request->request->get('capacity');
        $sessionType = $request->request->get('sessionType');
        $description = $request->request->get('description');

        // Validate required fields
        $missingFields = [];
        if (!$courseId) $missingFields[] = 'Course';
        if (!$location) $missingFields[] = 'Location';
        if (!$startDate) $missingFields[] = 'Start Date';
        if (!$endDate) $missingFields[] = 'End Date';
        if (!$time) $missingFields[] = 'Time';
        if (!$capacity) $missingFields[] = 'Capacity';

        if (!empty($missingFields)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Please fill out the following required fields: ' . implode(', ', $missingFields)
            ], 400);
        }

        // Validate that start date is not after end date
        try {
            $startDateObj = new \DateTime($startDate);
            $endDateObj = new \DateTime($endDate);

            if ($startDateObj > $endDateObj) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Start date cannot be after end date'
                ], 400);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid date format: ' . $e->getMessage()
            ], 400);
        }

        try {
            // Get the course
            $course = $entityManager->getRepository(Course::class)->find($courseId);
            if (!$course) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Update session
            $session->setCourse($course);
            $session->setLocation($location);

            try {
                // Parse the time (dates were already parsed during validation)
                $timeObj = new \DateTime($time);

                $session->setStartDate($startDateObj);
                $session->setEndDate($endDateObj);
                $session->setTime($timeObj);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid time format: ' . $e->getMessage(),
                    'time' => $time
                ], 400);
            }

            $session->setCapacity((int)$capacity);
            $session->setType($sessionType);
            $session->setDescription($description);
            $session->setUpdatedAt(new \DateTime());

            // Save to database
            $entityManager->flush();

            // In a real application, we would send notifications to students if $notifyStudents is true

            // Determine status based on date
            $now = new \DateTime();
            $startDate = $session->getStartDate();
            $endDate = $session->getEndDate();

            if ($startDate > $now) {
                $status = 'upcoming';
                $statusLabel = 'Upcoming';
                $statusClass = 'badge-success';
            } elseif ($endDate < $now) {
                $status = 'completed';
                $statusLabel = 'Completed';
                $statusClass = 'badge-info';
            } else {
                $status = 'in_progress';
                $statusLabel = 'In Progress';
                $statusClass = 'badge-warning';
            }

            // Prepare session data for response
            $sessionData = [
                'id' => $session->getId(),
                'course' => $course->getTitle(),
                'date' => $session->getStartDate()->format('Y-m-d'),
                'time' => $session->getTime()->format('H:i'),
                'capacity' => $session->getCapacity(),
                'enrolled' => count($session->getEnrollments()),
                'status' => $status,
                'statusLabel' => $statusLabel,
                'statusClass' => $statusClass
            ];

            return new JsonResponse([
                'success' => true,
                'message' => sprintf(
                    'Session for "%s" has been updated successfully. It will take place on %s at %s.',
                    $course->getTitle(),
                    $session->getStartDate()->format('F j, Y'),
                    $session->getTime()->format('g:i A')
                ),
                'session' => $sessionData
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/sessions/{id}/delete', name: 'instructor_session_delete', methods: ['POST'], priority: -10)]
    public function deleteSession(EntityManagerInterface $entityManager, Request $request, int $id): Response
    {
        // Get the session
        $session = $entityManager->getRepository(Session::class)->find($id);

        if (!$session) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        try {
            // Check if there are enrollments
            $enrollments = $session->getEnrollments();
            $hasEnrollments = count($enrollments) > 0;

            // Get confirmation for sessions with enrollments
            $data = json_decode($request->getContent(), true);
            $confirmed = isset($data['confirmed']) ? $data['confirmed'] : false;

            if ($hasEnrollments && !$confirmed) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'This session has enrolled students. Are you sure you want to delete it?',
                    'requiresConfirmation' => true,
                    'enrollmentsCount' => count($enrollments)
                ]);
            }

            // In a real application, we might want to notify enrolled students

            // Remove the session
            $entityManager->remove($session);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Session deleted successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/sessions/filter', name: 'instructor_sessions_filter', methods: ['POST'], priority: 10)]
    public function filterSessions(EntityManagerInterface $entityManager, Request $request): Response
    {
        // Get filter parameters
        $status = $request->request->get('status');
        $courseId = $request->request->get('course');
        $startDate = $request->request->get('startDate');
        $endDate = $request->request->get('endDate');

        // Get all sessions
        $sessions = $entityManager->getRepository(Session::class)->findAll();

        // Filter by status
        if ($status) {
            $now = new \DateTime();
            $filteredSessions = [];

            foreach ($sessions as $session) {
                $startDate = $session->getStartDate();
                $endDate = $session->getEndDate();

                if ($status === 'upcoming' && $startDate > $now) {
                    $filteredSessions[] = $session;
                } elseif ($status === 'in_progress' && $startDate <= $now && $endDate >= $now) {
                    $filteredSessions[] = $session;
                } elseif ($status === 'completed' && $endDate < $now) {
                    $filteredSessions[] = $session;
                }
            }

            $sessions = $filteredSessions;
        }

        // Filter by course
        if ($courseId) {
            $filteredSessions = [];

            foreach ($sessions as $session) {
                if ($session->getCourse()->getId() == $courseId) {
                    $filteredSessions[] = $session;
                }
            }

            $sessions = $filteredSessions;
        }

        // Filter by date range
        if ($startDate && $endDate) {
            $startDateTime = new \DateTime($startDate);
            $endDateTime = new \DateTime($endDate);
            $filteredSessions = [];

            foreach ($sessions as $session) {
                $startDate = $session->getStartDate();
                $endDate = $session->getEndDate();

                // Include session if any part of it falls within the filter range
                if (($startDate >= $startDateTime && $startDate <= $endDateTime) ||
                    ($endDate >= $startDateTime && $endDate <= $endDateTime) ||
                    ($startDate <= $startDateTime && $endDate >= $endDateTime)
                ) {
                    $filteredSessions[] = $session;
                }
            }

            $sessions = $filteredSessions;
        }

        // Prepare session data for JSON response
        $sessionData = [];
        foreach ($sessions as $session) {
            $now = new \DateTime();
            $startDate = $session->getStartDate();
            $endDate = $session->getEndDate();

            if ($startDate > $now) {
                $status = 'upcoming';
                $statusLabel = 'Upcoming';
                $statusClass = 'badge-success';
            } elseif ($endDate < $now) {
                $status = 'completed';
                $statusLabel = 'Completed';
                $statusClass = 'badge-info';
            } else {
                $status = 'in_progress';
                $statusLabel = 'In Progress';
                $statusClass = 'badge-warning';
            }

            $dateDisplay = $startDate->format('Y-m-d');
            if ($startDate != $endDate) {
                $dateDisplay .= ' to ' . $endDate->format('Y-m-d');
            }

            $sessionData[] = [
                'id' => $session->getId(),
                'course' => $session->getCourse()->getTitle(),
                'date' => $dateDisplay,
                'time' => $session->getTime()->format('H:i'),
                'capacity' => $session->getCapacity(),
                'enrolled' => count($session->getEnrollments()),
                'status' => $status,
                'statusLabel' => $statusLabel,
                'statusClass' => $statusClass
            ];
        }

        return new JsonResponse([
            'success' => true,
            'sessions' => $sessionData
        ]);
    }

    #[Route('/students/filter', name: 'instructor_students_filter', methods: ['POST'])]
    public function filterStudents(EntityManagerInterface $entityManager, Request $request): Response
    {
        // Get filter parameters
        $courseId = $request->request->get('course');
        $status = $request->request->get('status');
        $searchTerm = $request->request->get('search');

        // Get all sessions
        $sessions = $entityManager->getRepository(Session::class)->findAll();

        // Get all enrollments for these sessions
        $enrollments = [];
        foreach ($sessions as $session) {
            foreach ($session->getEnrollments() as $enrollment) {
                $enrollments[] = $enrollment;
            }
        }

        // Get unique students
        $students = [];
        foreach ($enrollments as $enrollment) {
            $student = $enrollment->getUsers();
            if ($student && !isset($students[$student->getId()])) {
                $students[$student->getId()] = [
                    'user' => $student,
                    'enrollments' => [],
                    'sessions' => [],
                    'courses' => []
                ];
            }

            if ($student) {
                $students[$student->getId()]['enrollments'][] = $enrollment;
                $session = $enrollment->getSession();
                if ($session) {
                    $students[$student->getId()]['sessions'][$session->getId()] = $session;
                    $course = $session->getCourse();
                    if ($course) {
                        $students[$student->getId()]['courses'][$course->getId()] = $course;
                    }
                }
            }
        }

        // Calculate additional statistics for each student
        foreach ($students as &$studentData) {
            $studentData['sessionsCount'] = count($studentData['sessions']);
            $studentData['coursesCount'] = count($studentData['courses']);
            $studentData['enrollmentsCount'] = count($studentData['enrollments']);
        }

        // Filter by course
        if ($courseId) {
            $filteredStudents = [];
            foreach ($students as $studentId => $studentData) {
                if (isset($studentData['courses'][$courseId])) {
                    $filteredStudents[$studentId] = $studentData;
                }
            }
            $students = $filteredStudents;
        }

        // Filter by status
        if ($status) {
            $now = new \DateTime();
            $filteredStudents = [];

            foreach ($students as $studentId => $studentData) {
                $hasActiveEnrollment = false;
                $hasCompletedEnrollment = false;

                foreach ($studentData['sessions'] as $session) {
                    $startDate = $session->getStartDate();
                    $endDate = $session->getEndDate();

                    if ($startDate <= $now && $endDate >= $now) {
                        // Session is currently active
                        $hasActiveEnrollment = true;
                    } elseif ($startDate > $now) {
                        // Session is upcoming
                        $hasActiveEnrollment = true;
                    } else {
                        // Session is completed
                        $hasCompletedEnrollment = true;
                    }
                }

                if (($status === 'active' && $hasActiveEnrollment) ||
                    ($status === 'completed' && $hasCompletedEnrollment && !$hasActiveEnrollment)
                ) {
                    $filteredStudents[$studentId] = $studentData;
                }
            }

            $students = $filteredStudents;
        }

        // Filter by search term
        if ($searchTerm) {
            $filteredStudents = [];
            $searchTerm = strtolower($searchTerm);

            foreach ($students as $studentId => $studentData) {
                $student = $studentData['user'];
                $fullName = strtolower($student->getFirstName() . ' ' . $student->getLastName());
                $email = strtolower($student->getEmail());

                if (strpos($fullName, $searchTerm) !== false || strpos($email, $searchTerm) !== false) {
                    $filteredStudents[$studentId] = $studentData;
                }
            }

            $students = $filteredStudents;
        }

        // Prepare student data for JSON response
        $studentData = [];
        foreach ($students as $studentId => $data) {
            $student = $data['user'];

            $studentData[] = [
                'id' => $student->getId(),
                'firstName' => $student->getFirstName(),
                'lastName' => $student->getLastName(),
                'email' => $student->getEmail(),
                'enrollmentsCount' => $data['enrollmentsCount'],
                'coursesCount' => $data['coursesCount'],
                'sessionsCount' => $data['sessionsCount'],
                'lastActivity' => (new \DateTime())->format('Y-m-d'),
                'image' => $student->getImage() ?? null,
                'initials' => $student->getFirstName()[0] . $student->getLastName()[0]
            ];
        }

        return new JsonResponse([
            'success' => true,
            'students' => $studentData
        ]);
    }

    #[Route('/students/{id}', name: 'instructor_student_details')]
    public function studentDetails(EntityManagerInterface $entityManager, int $id): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get the student
        $student = $entityManager->getRepository(User::class)->find($id);

        if (!$student) {
            throw $this->createNotFoundException('Student not found');
        }

        // Get all sessions
        $sessions = $entityManager->getRepository(Session::class)->findAll();

        // Get enrollments for this student
        $enrollments = [];
        $studentCourses = [];
        $studentSessions = [];

        foreach ($sessions as $session) {
            foreach ($session->getEnrollments() as $enrollment) {
                if ($enrollment->getUsers() && $enrollment->getUsers()->getId() === $student->getId()) {
                    $enrollments[] = $enrollment;
                    $studentSessions[$session->getId()] = $session;
                    $course = $session->getCourse();
                    if ($course) {
                        $studentCourses[$course->getId()] = $course;
                    }
                }
            }
        }

        // Generate dummy progress data
        $progressData = [
            'overall' => rand(60, 95),
            'attendance' => rand(70, 100),
            'completion' => rand(60, 100),
            'participation' => rand(50, 100),
            'courses' => []
        ];

        // Generate dummy course progress data
        foreach ($studentCourses as $course) {
            $progressData['courses'][] = [
                'course' => $course,
                'progress' => rand(50, 100),
                'grade' => ['A', 'B', 'C', 'B+', 'A-'][rand(0, 4)]
            ];
        }

        return $this->render('instructor/student_details.html.twig', [
            'categories' => $categories,
            'student' => $student,
            'enrollments' => $enrollments,
            'courses' => $studentCourses,
            'sessions' => $studentSessions,
            'progressData' => $progressData
        ]);
    }

    #[Route('/students/{id}/courses', name: 'instructor_student_courses')]
    public function studentCourses(EntityManagerInterface $entityManager, int $id): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get the student
        $student = $entityManager->getRepository(User::class)->find($id);

        if (!$student) {
            throw $this->createNotFoundException('Student not found');
        }

        // Get all sessions
        $sessions = $entityManager->getRepository(Session::class)->findAll();

        // Get enrollments for this student
        $enrollments = [];
        $studentCourses = [];

        foreach ($sessions as $session) {
            foreach ($session->getEnrollments() as $enrollment) {
                if ($enrollment->getUsers() && $enrollment->getUsers()->getId() === $student->getId()) {
                    $enrollments[] = $enrollment;
                    $course = $session->getCourse();
                    if ($course) {
                        if (!isset($studentCourses[$course->getId()])) {
                            $studentCourses[$course->getId()] = [
                                'course' => $course,
                                'sessions' => [],
                                'progress' => rand(50, 100),
                                'grade' => ['A', 'B', 'C', 'B+', 'A-'][rand(0, 4)]
                            ];
                        }
                        $studentCourses[$course->getId()]['sessions'][] = $session;
                    }
                }
            }
        }

        return $this->render('instructor/student_courses.html.twig', [
            'categories' => $categories,
            'student' => $student,
            'courses' => $studentCourses
        ]);
    }

    #[Route('/students/{id}/sessions', name: 'instructor_student_sessions')]
    public function studentSessions(EntityManagerInterface $entityManager, int $id): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get the student
        $student = $entityManager->getRepository(User::class)->find($id);

        if (!$student) {
            throw $this->createNotFoundException('Student not found');
        }

        // Get all sessions
        $allSessions = $entityManager->getRepository(Session::class)->findAll();

        // Get enrollments for this student
        $enrollments = [];
        $studentSessions = [];

        foreach ($allSessions as $session) {
            foreach ($session->getEnrollments() as $enrollment) {
                if ($enrollment->getUsers() && $enrollment->getUsers()->getId() === $student->getId()) {
                    $enrollments[] = $enrollment;
                    $studentSessions[] = [
                        'session' => $session,
                        'course' => $session->getCourse(),
                        'enrollment' => $enrollment,
                        'attendance' => rand(0, 1) > 0.2, // 80% chance of attendance
                        'progress' => rand(50, 100)
                    ];
                }
            }
        }

        // Sort sessions by date (newest first)
        usort($studentSessions, function ($a, $b) {
            return $b['session']->getDate() <=> $a['session']->getDate();
        });

        return $this->render('instructor/student_sessions.html.twig', [
            'categories' => $categories,
            'student' => $student,
            'sessions' => $studentSessions
        ]);
    }

    #[Route('/students/{id}/progress', name: 'instructor_student_progress')]
    public function studentProgress(EntityManagerInterface $entityManager, int $id): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get the student
        $student = $entityManager->getRepository(User::class)->find($id);

        if (!$student) {
            throw $this->createNotFoundException('Student not found');
        }

        // Get all sessions
        $sessions = $entityManager->getRepository(Session::class)->findAll();

        // Get enrollments for this student
        $enrollments = [];
        $studentCourses = [];

        foreach ($sessions as $session) {
            foreach ($session->getEnrollments() as $enrollment) {
                if ($enrollment->getUsers() && $enrollment->getUsers()->getId() === $student->getId()) {
                    $enrollments[] = $enrollment;
                    $course = $session->getCourse();
                    if ($course) {
                        $studentCourses[$course->getId()] = $course;
                    }
                }
            }
        }

        // Generate dummy progress data
        $progressData = [
            'overall' => rand(60, 95),
            'attendance' => rand(70, 100),
            'completion' => rand(60, 100),
            'participation' => rand(50, 100),
            'courses' => [],
            'recentActivities' => []
        ];

        // Generate dummy course progress data
        foreach ($studentCourses as $course) {
            $progressData['courses'][] = [
                'course' => $course,
                'progress' => rand(50, 100),
                'grade' => ['A', 'B', 'C', 'B+', 'A-'][rand(0, 4)]
            ];
        }

        // Generate dummy recent activities
        $activityTypes = ['completed_assignment', 'attended_session', 'submitted_quiz', 'viewed_lecture'];
        for ($i = 0; $i < 5; $i++) {
            $activityType = $activityTypes[array_rand($activityTypes)];
            $course = array_values($studentCourses)[array_rand($studentCourses)];

            $progressData['recentActivities'][] = [
                'type' => $activityType,
                'course' => $course,
                'date' => (new \DateTime())->modify('-' . rand(1, 14) . ' days'),
                'score' => $activityType === 'submitted_quiz' ? rand(70, 100) . '%' : null
            ];
        }

        // Sort recent activities by date (newest first)
        usort($progressData['recentActivities'], function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return $this->render('instructor/student_progress.html.twig', [
            'categories' => $categories,
            'student' => $student,
            'enrollments' => $enrollments,
            'courses' => $studentCourses,
            'progressData' => $progressData
        ]);
    }

    #[Route('/materials', name: 'instructor_materials')]
    public function materials(EntityManagerInterface $entityManager): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get all courses
        // In a real application, we would filter courses by instructor
        $courses = $entityManager->getRepository(Course::class)->findAll();

        // Get course materials
        $courseMaterials = [];

        foreach ($courses as $course) {
            $materials = $entityManager->getRepository(Material::class)->findByCourse($course->getId());

            $documents = array_filter($materials, function ($material) {
                return $material->getType() === 'document';
            });

            $courseMaterials[$course->getId()] = [
                'documents' => $documents
            ];
        }

        return $this->render('instructor/materials.html.twig', [
            'categories' => $categories,
            'courses' => $courses,
            'courseMaterials' => $courseMaterials,
        ]);
    }

    #[Route('/materials/create', name: 'instructor_material_create', methods: ['POST'])]
    public function createMaterial(EntityManagerInterface $entityManager, Request $request): Response
    {
        // Get form data
        $courseId = $request->request->get('course');
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $type = 'document'; // We're only handling documents now

        // Get the uploaded file
        $file = $request->files->get('file');

        // Validate required fields
        if (!$courseId || !$title || !$file) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Course, title and file are required'
            ], 400);
        }

        try {
            // Debug request data
            error_log('Course ID: ' . $courseId);
            error_log('Title: ' . $title);
            error_log('File: ' . ($file ? 'Present' : 'Not present'));

            // Get the course
            $course = $entityManager->getRepository(Course::class)->find($courseId);
            if (!$course) {
                error_log('Course not found with ID: ' . $courseId);
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Get file information before moving it
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $fileExtension = $file->getClientOriginalExtension();
            $fileSize = $file->getSize();

            // Check if transliterator is available
            if (function_exists('transliterator_transliterate')) {
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
            } else {
                // Fallback if transliterator is not available
                $safeFilename = preg_replace('/[^A-Za-z0-9_]/', '', strtolower($originalFilename));
            }

            $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

            // Move the file to the uploads directory
            $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/materials';

            // Create directory if it doesn't exist
            if (!file_exists($uploadsDirectory)) {
                mkdir($uploadsDirectory, 0777, true);

                // On Windows, try to set permissions
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    try {
                        // Try to make the directory writable
                        chmod($uploadsDirectory, 0777);
                    } catch (\Exception $e) {
                        error_log('Could not set permissions on uploads directory: ' . $e->getMessage());
                    }
                }
            }

            $file->move($uploadsDirectory, $newFilename);

            // Create new material
            $material = new Material();
            $material->setCourse($course);
            $material->setTitle($title);
            $material->setDescription($description);
            $material->setType($type);
            $material->setFilePath('/uploads/materials/' . $newFilename);
            $material->setFileType($fileExtension);
            $material->setFileSize($this->formatBytes($fileSize));
            $material->setCreatedAt(new \DateTime());

            // Save to database
            $entityManager->persist($material);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Material created successfully',
                'material' => [
                    'id' => $material->getId(),
                    'title' => $material->getTitle(),
                    'type' => $material->getFileType(),
                    'size' => $material->getFileSize()
                ]
            ]);
        } catch (\Exception $e) {
            // Log the error
            error_log('Error creating material: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/materials/{id}/edit', name: 'instructor_material_edit', methods: ['POST'])]
    public function editMaterial(EntityManagerInterface $entityManager, Request $request, int $id): Response
    {
        // Get the material
        $material = $entityManager->getRepository(Material::class)->find($id);

        if (!$material) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Material not found'
            ], 404);
        }

        // Get form data
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $file = $request->files->get('file');

        // Validate required fields
        if (!$title) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Title is required'
            ], 400);
        }

        try {
            // Update material
            $material->setTitle($title);
            $material->setDescription($description);
            $material->setUpdatedAt(new \DateTime());

            // If a new file is uploaded, update the file information
            if ($file) {
                // Delete the old file if it exists
                $oldFilePath = $this->getParameter('kernel.project_dir') . '/public' . $material->getFilePath();
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }

                // Get file information before moving it
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $fileExtension = $file->getClientOriginalExtension();
                $fileSize = $file->getSize();

                // Check if transliterator is available
                if (function_exists('transliterator_transliterate')) {
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                } else {
                    // Fallback if transliterator is not available
                    $safeFilename = preg_replace('/[^A-Za-z0-9_]/', '', strtolower($originalFilename));
                }

                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                // Move the file to the uploads directory
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/materials';

                // Create directory if it doesn't exist
                if (!file_exists($uploadsDirectory)) {
                    mkdir($uploadsDirectory, 0777, true);

                    // On Windows, try to set permissions
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        try {
                            // Try to make the directory writable
                            chmod($uploadsDirectory, 0777);
                        } catch (\Exception $e) {
                            error_log('Could not set permissions on uploads directory: ' . $e->getMessage());
                        }
                    }
                }

                $file->move($uploadsDirectory, $newFilename);

                // Update material with new file information
                $material->setFilePath('/uploads/materials/' . $newFilename);
                $material->setFileType($fileExtension);
                $material->setFileSize($this->formatBytes($fileSize));
            }

            // Save to database
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Material updated successfully',
                'material' => [
                    'id' => $material->getId(),
                    'title' => $material->getTitle(),
                    'type' => $material->getFileType(),
                    'size' => $material->getFileSize()
                ]
            ]);
        } catch (\Exception $e) {
            // Log the error
            error_log('Error editing material: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/materials/{id}/delete', name: 'instructor_material_delete', methods: ['POST'])]
    public function deleteMaterial(EntityManagerInterface $entityManager, int $id): Response
    {
        // Get the material
        $material = $entityManager->getRepository(Material::class)->find($id);

        if (!$material) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Material not found'
            ], 404);
        }

        try {
            // Delete the file if it exists
            $filePath = $this->getParameter('kernel.project_dir') . '/public' . $material->getFilePath();
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Remove the material
            $entityManager->remove($material);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Material deleted successfully'
            ]);
        } catch (\Exception $e) {
            // Log the error
            error_log('Error deleting material: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
