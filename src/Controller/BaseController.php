<?php

namespace App\Controller;

use App\Entity\Category;
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

        return [
            'categories' => $categories,
            'generalSettings' => $generalSettings,
            'appearanceSettings' => $appearanceSettings,
            'maintenanceSettings' => $maintenanceSettings,
        ];
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
