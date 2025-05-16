<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Session;
use App\Entity\Category;
use App\Entity\User;
use App\Form\CourseType;
use App\Form\SessionType;
use App\Form\UserType;
use App\Form\CategoryType;
use App\Service\SettingsService;
use App\Service\ReportService;
use Symfony\Component\Filesystem\Filesystem;
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
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $directory = $this->getParameter('imageDirectory');
                    $photoFile->move($directory, $newFilename);

                    $course->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error while uploading : ' . $e->getMessage());
                    return $this->redirectToRoute('admin_course_edit', ['id' => $course->getId()]);
                }
            }
            // Handle PDF syllabus upload
            $syllabusFile = $form->get('syllabus')->getData();

            if ($syllabusFile) {
                $originalFilename = pathinfo($syllabusFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $syllabusFile->guessExtension();

                try {
                    $directory = $this->getParameter('syllabusDirectory');
                    $syllabusFile->move($directory, $newFilename);
                    $course->setSyllabus($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error while uploading syllabus: ' . $e->getMessage());
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
    public function editCourse(Request $request, Course $course, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle syllabus upload
            $syllabusFile = $form->get('syllabus')->getData();

            if ($syllabusFile) {
                $originalFilename = pathinfo($syllabusFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $syllabusFile->guessExtension();

                try {
                    $directory = $this->getParameter('syllabusDirectory');
                    $syllabusFile->move($directory, $newFilename);
                    $course->setSyllabus($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error while uploading syllabus: ' . $e->getMessage());
                    return $this->redirectToRoute('admin_course_edit', ['id' => $course->getId()]);
                }
            }
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $directory = $this->getParameter('imageDirectory');
                    $photoFile->move($directory, $newFilename);

                    $course->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error while uploading : ' . $e->getMessage());
                    return $this->redirectToRoute('admin_course_edit', ['id' => $course->getId()]);
                }
            }

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

    #[Route('/admin/users', name: 'admin_users')]
    public function users(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get all users
        $users = $entityManager->getRepository(User::class)->findAll();

        // Create form for new user
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encode the password
            $user->setPassword(
                password_hash($form->get('password')->getData(), PASSWORD_BCRYPT)
            );

            // Handle file upload
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $newFilename = $this->handleFileUpload($photoFile, 'avatars');
                $user->setImage($newFilename);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'User created successfully.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users.html.twig', [
            'categories' => $categories,
            'users' => $users,
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/users/{id}/edit', name: 'admin_user_edit')]
    public function editUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        $form = $this->createForm(UserType::class, $user, [
            'is_new_user' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle password change if provided
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    password_hash($plainPassword, PASSWORD_BCRYPT)
                );
            }

            // Handle file upload
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $newFilename = $this->handleFileUpload($photoFile, 'avatars');
                $user->setImage($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'User updated successfully.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user_edit.html.twig', [
            'categories' => $categories,
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('admin_users');
    }

    private function handleFileUpload($file, $directory): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Determine the upload directory based on the directory parameter
        $uploadDir = 'imageDirectory';
        if ($directory === 'avatars') {
            $uploadDir = 'imageUserDirectory';
        } elseif ($directory === 'logos') {
            $uploadDir = 'logosDirectory';
        } elseif ($directory === 'favicons') {
            $uploadDir = 'faviconsDirectory';
        }

        try {
            // Create the directory if it doesn't exist
            $filesystem = new Filesystem();
            $dirPath = $this->getParameter($uploadDir);

            if (!$filesystem->exists($dirPath)) {
                $filesystem->mkdir($dirPath);
            }

            $file->move(
                $dirPath,
                $newFilename
            );
        } catch (\Exception $e) {
            throw new \Exception('Error uploading file: ' . $e->getMessage());
        }

        return $newFilename;
    }

    #[Route('/admin/categories', name: 'admin_categories')]
    public function categories(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Create form for new category
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'Category created successfully.');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/categories.html.twig', [
            'categories' => $categories,
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/categories/{id}/edit', name: 'admin_category_edit', methods: ['POST'])]
    public function editCategory(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);

        // Only handle the form data, not the entire request
        $form->submit([
            'name' => $request->request->get('name'),
            'description' => $request->request->get('description')
        ]);

        if ($form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Category updated successfully.');
        } else {
            $this->addFlash('danger', 'There was an error updating the category.');
        }

        return $this->redirectToRoute('admin_categories');
    }

    #[Route('/admin/categories/{id}/delete', name: 'admin_category_delete', methods: ['POST'])]
    public function deleteCategory(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            // Check if category has courses
            if (count($category->getCourses()) > 0) {
                $this->addFlash('warning', 'Cannot delete category that has courses. Please remove the courses first or reassign them to another category.');
            } else {
                $entityManager->remove($category);
                $entityManager->flush();
                $this->addFlash('success', 'Category deleted successfully.');
            }
        }

        return $this->redirectToRoute('admin_categories');
    }

    #[Route('/admin/reports', name: 'admin_reports')]
    public function reports(EntityManagerInterface $entityManager, ReportService $reportService): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Get system statistics
        $systemStats = $reportService->getSystemStats();

        // Get system health information
        $systemHealth = $reportService->getSystemHealth();

        // Get monthly activity data for charts
        $activityData = $reportService->getMonthlyActivityData();

        return $this->render('admin/reports.html.twig', [
            'categories' => $categories,
            'coursesCount' => $systemStats['coursesCount'],
            'sessionsCount' => $systemStats['sessionsCount'],
            'usersCount' => $systemStats['usersCount'],
            'usersByRole' => $systemStats['usersByRole'],
            'systemHealth' => $systemHealth,
            'activityData' => $activityData
        ]);
    }

    #[Route('/admin/reports/export/users', name: 'admin_export_users')]
    public function exportUsers(ReportService $reportService): Response
    {
        return $reportService->generateUserReport();
    }

    #[Route('/admin/reports/export/courses', name: 'admin_export_courses')]
    public function exportCourses(ReportService $reportService): Response
    {
        return $reportService->generateCourseReport();
    }

    #[Route('/admin/reports/export/system-log', name: 'admin_export_system_log')]
    public function exportSystemLog(ReportService $reportService): Response
    {
        return $reportService->generateSystemLogReport();
    }

    #[Route('/admin/settings', name: 'admin_settings')]
    public function settings(Request $request, EntityManagerInterface $entityManager, SettingsService $settingsService): Response
    {
        // Get all categories for the sidebar
        $categories = $entityManager->getRepository(Category::class)->findAll();

        // Initialize default settings if they don't exist
        $settingsService->initializeDefaults();

        // Get settings by category
        $generalSettings = $settingsService->getByCategory('general');
        $emailSettings = $settingsService->getByCategory('email');
        $securitySettings = $settingsService->getByCategory('security');
        $appearanceSettings = $settingsService->getByCategory('appearance');
        $maintenanceSettings = $settingsService->getByCategory('maintenance');

        // Handle form submissions
        $activeTab = 'general';

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $category = $request->request->get('category');
            $activeTab = $category;

            // Remove the category field from the data
            unset($formData['category']);

            // Handle file uploads for appearance settings
            if ($category === 'appearance') {
                $logoFile = $request->files->get('logo');
                if ($logoFile) {
                    $newFilename = $this->handleFileUpload($logoFile, 'logos');
                    $formData['logo'] = $newFilename;
                }

                $faviconFile = $request->files->get('favicon');
                if ($faviconFile) {
                    $newFilename = $this->handleFileUpload($faviconFile, 'favicons');
                    $formData['favicon'] = $newFilename;
                }
            }

            // Save the settings
            $settingsService->saveMultiple($formData, $category);

            $this->addFlash('success', ucfirst($category) . ' settings updated successfully.');

            // Redirect to prevent form resubmission
            return $this->redirectToRoute('admin_settings', ['tab' => $category]);
        }

        // Check if a tab is specified in the URL
        if ($request->query->has('tab')) {
            $activeTab = $request->query->get('tab');
        }

        return $this->render('admin/settings.html.twig', [
            'categories' => $categories,
            'generalSettings' => $generalSettings,
            'emailSettings' => $emailSettings,
            'securitySettings' => $securitySettings,
            'appearanceSettings' => $appearanceSettings,
            'maintenanceSettings' => $maintenanceSettings,
            'activeTab' => $activeTab
        ]);
    }

    #[Route('/admin/settings/clear-cache', name: 'admin_clear_cache')]
    public function clearCache(): Response
    {
        // Clear the cache directory
        $filesystem = new Filesystem();
        $cacheDir = $this->getParameter('kernel.cache_dir');

        try {
            if ($filesystem->exists($cacheDir)) {
                $filesystem->remove($cacheDir);
            }

            $this->addFlash('success', 'Cache cleared successfully.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Error clearing cache: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_settings', ['tab' => 'maintenance']);
    }

    #[Route('/admin/settings/backup', name: 'admin_backup_database')]
    public function backupDatabase(): Response
    {
        // Create a backup directory if it doesn't exist
        $filesystem = new Filesystem();
        $backupDir = $this->getParameter('kernel.project_dir') . '/var/backups';

        try {
            if (!$filesystem->exists($backupDir)) {
                $filesystem->mkdir($backupDir);
            }

            // Create a backup file with current timestamp
            $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.json';

            // Get the settings service
            $settingsService = $this->container->get(SettingsService::class);

            // Get all settings
            $settings = [
                'general' => $settingsService->getByCategory('general'),
                'email' => $settingsService->getByCategory('email'),
                'security' => $settingsService->getByCategory('security'),
                'appearance' => $settingsService->getByCategory('appearance'),
                'maintenance' => $settingsService->getByCategory('maintenance')
            ];

            // Write the settings to the backup file
            file_put_contents($backupFile, json_encode($settings, JSON_PRETTY_PRINT));

            $this->addFlash('success', 'Database backup created successfully.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Error creating backup: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_settings', ['tab' => 'maintenance']);
    }
}
