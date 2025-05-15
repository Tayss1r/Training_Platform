<?php

namespace App\Twig;

use App\Service\SettingsService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    private $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function getGlobals(): array
    {
        // Initialize default settings if they don't exist
        $this->settingsService->initializeDefaults();
        
        // Get settings by category
        $generalSettings = $this->settingsService->getByCategory('general');
        $emailSettings = $this->settingsService->getByCategory('email');
        $securitySettings = $this->settingsService->getByCategory('security');
        $appearanceSettings = $this->settingsService->getByCategory('appearance');
        $maintenanceSettings = $this->settingsService->getByCategory('maintenance');
        
        return [
            'generalSettings' => $generalSettings,
            'emailSettings' => $emailSettings,
            'securitySettings' => $securitySettings,
            'appearanceSettings' => $appearanceSettings,
            'maintenanceSettings' => $maintenanceSettings,
        ];
    }
}
