<?php

namespace App\Controller;

use App\Entity\Announcement;
use App\Entity\Category;
use App\Entity\User;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends AbstractController
{
    protected $entityManager;
    protected $settingsService;

    public function __construct(EntityManagerInterface $entityManager, SettingsService $settingsService)
    {
        $this->entityManager = $entityManager;
        $this->settingsService = $settingsService;
    }

    /**
     * Get common data for all templates
     */
    protected function getCommonData(): array
    {
        // Initialize default settings if they don't exist
        $this->settingsService->initializeDefaults();

        // Get all categories for the sidebar
        $categories = $this->entityManager->getRepository(Category::class)->findAll();

        // Get general and appearance settings
        $generalSettings = $this->settingsService->getByCategory('general');
        $appearanceSettings = $this->settingsService->getByCategory('appearance');
        $maintenanceSettings = $this->settingsService->getByCategory('maintenance');

        // Get unread announcements count for students
        $unreadAnnouncementsCount = 0;
        if ($this->isGranted('ROLE_USER') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_INSTRUCTOR')) {
            $unreadAnnouncementsCount = $this->getUnreadAnnouncementsCount();
        }

        return [
            'categories' => $categories,
            'generalSettings' => $generalSettings,
            'appearanceSettings' => $appearanceSettings,
            'maintenanceSettings' => $maintenanceSettings,
            'unreadAnnouncementsCount' => $unreadAnnouncementsCount,
        ];
    }

    /**
     * Get the count of unread announcements for the current user
     */
    protected function getUnreadAnnouncementsCount(): int
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return 0;
        }

        $announcementRepository = $this->entityManager->getRepository(Announcement::class);
        $unreadAnnouncements = $announcementRepository->findUnreadByUser($user);

        return count($unreadAnnouncements);
    }

    /**
     * Override the render method to include common data
     */
    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $commonData = $this->getCommonData();
        $parameters = array_merge($commonData, $parameters);

        // Maintenance mode is now handled by the MaintenanceListener
        return parent::render($view, $parameters, $response);
    }
}
