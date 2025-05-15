<?php

namespace App\Controller;

use App\Entity\Announcement;
use App\Entity\AnnouncementRead;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/student')]
class StudentController extends BaseController
{
    public function __construct(EntityManagerInterface $entityManager, \App\Service\SettingsService $settingsService)
    {
        parent::__construct($entityManager, $settingsService);
    }

    #[Route('/dashboard', name: 'student_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('student/dashboard.html.twig');
    }

    #[Route('/announcements', name: 'student_announcements')]
    public function announcements(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Get all announcements for sessions the student is enrolled in
        $announcementRepository = $entityManager->getRepository(Announcement::class);
        $announcements = $announcementRepository->findByEnrolledUser($user);

        // Format announcements for the view
        $formattedAnnouncements = [];
        foreach ($announcements as $announcement) {
            $session = $announcement->getSession();
            $isRead = false;

            // Check if the announcement has been read by the user
            foreach ($announcement->getAnnouncementReads() as $read) {
                if ($read->getUser() === $user) {
                    $isRead = true;
                    break;
                }
            }

            $formattedAnnouncements[] = [
                'id' => $announcement->getId(),
                'subject' => $announcement->getSubject(),
                'message' => $announcement->getMessage(),
                'sender' => $announcement->getSender()->getFirstName() . ' ' . $announcement->getSender()->getLastName(),
                'session' => $session->getCourse()->getTitle(),
                'date' => $announcement->getCreatedAt()->format('F j, Y, g:i a'),
                'isRead' => $isRead
            ];
        }

        return $this->render('student/announcements.html.twig', [
            'announcements' => $formattedAnnouncements
        ]);
    }

    #[Route('/announcements/{id}/read', name: 'student_announcement_read', methods: ['POST'])]
    public function markAnnouncementAsRead(EntityManagerInterface $entityManager, int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Get the announcement
        $announcement = $entityManager->getRepository(Announcement::class)->find($id);

        if (!$announcement) {
            return $this->json([
                'success' => false,
                'message' => 'Announcement not found'
            ], 404);
        }

        // Check if the user is enrolled in the session
        $session = $announcement->getSession();
        $isEnrolled = false;

        foreach ($session->getEnrollments() as $enrollment) {
            if ($enrollment->getUsers() === $user) {
                $isEnrolled = true;
                break;
            }
        }

        if (!$isEnrolled) {
            return $this->json([
                'success' => false,
                'message' => 'You are not enrolled in this session'
            ], 403);
        }

        // Check if the announcement has already been read by the user
        $announcementReadRepository = $entityManager->getRepository(AnnouncementRead::class);
        $isRead = $announcementReadRepository->isReadByUser($announcement, $user);

        if (!$isRead) {
            // Mark the announcement as read
            $announcementRead = new AnnouncementRead();
            $announcementRead->setAnnouncement($announcement);
            $announcementRead->setUser($user);

            $entityManager->persist($announcementRead);
            $entityManager->flush();
        }

        return $this->json([
            'success' => true,
            'message' => 'Announcement marked as read'
        ]);
    }

    #[Route('/announcements/count', name: 'student_announcements_count', methods: ['GET'])]
    public function getUnreadAnnouncementsCountAction(): Response
    {
        $count = parent::getUnreadAnnouncementsCount();

        return $this->json([
            'count' => $count
        ]);
    }
}
