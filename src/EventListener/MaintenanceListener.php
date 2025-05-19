<?php

namespace App\EventListener;

use App\Service\SettingsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class MaintenanceListener implements EventSubscriberInterface
{
    private $settingsService;
    private $twig;
    private $security;
    private $kernel;

    public function __construct(
        SettingsService $settingsService,
        Environment $twig,
        Security $security,
        KernelInterface $kernel
    ) {
        $this->settingsService = $settingsService;
        $this->twig = $twig;
        $this->security = $security;
        $this->kernel = $kernel;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Initialize default settings if they don't exist
        $this->settingsService->initializeDefaults();

        // Check the file directly
        $maintenanceMode = '0';
        $kernelProjectDir = $this->kernel->getProjectDir();
        $settingsFile = $kernelProjectDir . '/var/settings.json';

        if (file_exists($settingsFile)) {
            $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
            if (isset($settings['maintenance']['maintenance_mode'])) {
                $maintenanceMode = $settings['maintenance']['maintenance_mode'];
            }
        }

        // Also get from service for comparison
        $serviceMaintenance = $this->settingsService->get('maintenance_mode', '0');

        // Log the current maintenance mode settings for debugging
        error_log('Maintenance mode from file: ' . $maintenanceMode);
        error_log('Maintenance mode from service: ' . $serviceMaintenance);

        // Check if maintenance mode is enabled (use file value)
        if ($maintenanceMode === '1') {
            // Get the request
            $request = $event->getRequest();

            // Get the current route and path
            $route = $request->attributes->get('_route');
            $path = $request->getPathInfo();

            // Skip maintenance mode for login, logout, and maintenance-related routes
            $bypassRoutes = ['app_login', 'app_logout', 'admin_bypass_maintenance', 'admin_disable_maintenance'];
            if (in_array($route, $bypassRoutes)) {
                return;
            }

            // Check for session bypass flag
            $session = $request->getSession();
            if ($session->has('bypass_maintenance') && $session->get('bypass_maintenance') === true) {
                return;
            }

            // Allow admin users to access admin pages
            $user = $this->security->getUser();
            if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
                // Check if the path starts with /admin/
                if (strpos($path, '/admin/') === 0) {
                    return;
                }
            }

            // Get all settings for the maintenance page
            $generalSettings = $this->settingsService->getByCategory('general');
            $appearanceSettings = $this->settingsService->getByCategory('appearance');
            $maintenanceSettings = $this->settingsService->getByCategory('maintenance');

            // Render the maintenance page
            $content = $this->twig->render('maintenance.html.twig', [
                'generalSettings' => $generalSettings,
                'appearanceSettings' => $appearanceSettings,
                'maintenanceSettings' => $maintenanceSettings,
                'is_admin' => ($user && in_array('ROLE_ADMIN', $user->getRoles()))
            ]);

            $response = new Response($content, Response::HTTP_SERVICE_UNAVAILABLE);
            $event->setResponse($response);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }
}
