<?php

namespace App\Controller;

use App\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MaintenanceController extends AbstractController
{
    private $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    #[Route('/admin/bypass-maintenance', name: 'admin_bypass_maintenance')]
    #[IsGranted('ROLE_ADMIN')]
    public function bypassMaintenance(Request $request): Response
    {
        // Set a session flag to bypass maintenance mode
        $request->getSession()->set('bypass_maintenance', true);

        // Add a flash message
        $this->addFlash('success', 'You are now bypassing maintenance mode. You can browse the site normally.');

        // Redirect to the admin dashboard
        return new RedirectResponse($this->generateUrl('admin_dashboard'));
    }

    #[Route('/admin/end-bypass', name: 'admin_end_bypass')]
    #[IsGranted('ROLE_ADMIN')]
    public function endBypass(Request $request): Response
    {
        // Remove the session flag
        $request->getSession()->remove('bypass_maintenance');

        // Add a flash message
        $this->addFlash('info', 'You are no longer bypassing maintenance mode.');

        // Redirect to the admin dashboard
        return new RedirectResponse($this->generateUrl('admin_dashboard'));
    }

    #[Route('/admin/disable-maintenance', name: 'admin_disable_maintenance')]
    #[IsGranted('ROLE_ADMIN')]
    public function disableMaintenance(Request $request): Response
    {
        try {
            // Get the settings file path
            $settingsFile = $this->getParameter('kernel.project_dir') . '/var/settings.json';

            // Check if the file exists
            if (file_exists($settingsFile)) {
                // Read the current settings
                $settings = json_decode(file_get_contents($settingsFile), true) ?: [];

                // Update the maintenance mode setting
                if (isset($settings['maintenance'])) {
                    $settings['maintenance']['maintenance_mode'] = '0';
                } else {
                    $settings['maintenance'] = ['maintenance_mode' => '0'];
                }

                // Write the settings back to the file
                file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));

                // Also update via the service
                $this->settingsService->set('maintenance_mode', '0', 'maintenance');
                $this->settingsService->reloadSettings();
            } else {
                // If the file doesn't exist, create it with default settings
                $this->settingsService->initializeDefaults();
                $this->settingsService->set('maintenance_mode', '0', 'maintenance');
            }

            // Remove any bypass flag
            $request->getSession()->remove('bypass_maintenance');

            // Add a flash message
            $this->addFlash('success', 'Maintenance mode has been disabled.');

            // Log the action for debugging
            error_log('Maintenance mode disabled by admin');
        } catch (\Exception $e) {
            // Log any errors
            error_log('Error disabling maintenance mode: ' . $e->getMessage());
            $this->addFlash('danger', 'Error disabling maintenance mode: ' . $e->getMessage());
        }

        // Redirect to the settings page
        return new RedirectResponse($this->generateUrl('admin_settings', ['tab' => 'maintenance']));
    }

    #[Route('/admin/view-settings', name: 'admin_view_settings')]
    #[IsGranted('ROLE_ADMIN')]
    public function viewSettings(): Response
    {
        // Force reload settings from file
        $this->settingsService->reloadSettings();

        // Get all settings
        $settings = [
            'general' => $this->settingsService->getByCategory('general'),
            'email' => $this->settingsService->getByCategory('email'),
            'security' => $this->settingsService->getByCategory('security'),
            'appearance' => $this->settingsService->getByCategory('appearance'),
            'maintenance' => $this->settingsService->getByCategory('maintenance')
        ];

        // Get the raw file content
        $settingsFile = $this->getParameter('kernel.project_dir') . '/var/settings.json';
        $fileContent = file_exists($settingsFile) ? file_get_contents($settingsFile) : 'File not found';

        // Add file content to the response
        $response = [
            'settings' => $settings,
            'raw_file' => $fileContent,
            'maintenance_mode_direct' => $this->settingsService->get('maintenance_mode', 'not found')
        ];

        // Return as JSON
        return new Response(
            json_encode($response, JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }

    #[Route('/admin/reset-maintenance', name: 'admin_reset_maintenance')]
    #[IsGranted('ROLE_ADMIN')]
    public function resetMaintenance(Request $request): Response
    {
        // Force reset maintenance mode
        $this->settingsService->set('maintenance_mode', '0', 'maintenance');
        $this->settingsService->set('maintenance_message', 'We are currently performing scheduled maintenance. Please check back soon.', 'maintenance');

        // Remove any bypass flag
        $request->getSession()->remove('bypass_maintenance');

        // Add a flash message
        $this->addFlash('success', 'Maintenance settings have been reset.');

        // Redirect to the settings page
        return new RedirectResponse($this->generateUrl('admin_settings', ['tab' => 'maintenance']));
    }

    #[Route('/admin/toggle-maintenance/{state}', name: 'admin_toggle_maintenance')]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleMaintenance(string $state, Request $request): Response
    {
        // Get the settings file path
        $settingsFile = $this->getParameter('kernel.project_dir') . '/var/settings.json';

        try {
            // Ensure the state is valid
            $newState = ($state === 'on') ? '1' : '0';

            // Direct file manipulation
            if (file_exists($settingsFile)) {
                $settings = json_decode(file_get_contents($settingsFile), true) ?: [];

                // Make sure maintenance category exists
                if (!isset($settings['maintenance'])) {
                    $settings['maintenance'] = [];
                }

                // Set the maintenance mode
                $settings['maintenance']['maintenance_mode'] = $newState;

                // Write back to file
                file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));

                // Log the change
                error_log("Maintenance mode set to {$newState} via direct file manipulation");
            }

            // Also update via service
            $this->settingsService->set('maintenance_mode', $newState, 'maintenance');
            $this->settingsService->reloadSettings();

            // Add a flash message
            $message = ($newState === '1') ? 'Maintenance mode has been enabled.' : 'Maintenance mode has been disabled.';
            $this->addFlash('success', $message);

            // Remove bypass flag if disabling
            if ($newState === '0') {
                $request->getSession()->remove('bypass_maintenance');
            }
        } catch (\Exception $e) {
            // Log any errors
            error_log('Error toggling maintenance mode: ' . $e->getMessage());
            $this->addFlash('danger', 'Error toggling maintenance mode: ' . $e->getMessage());
        }

        // Redirect to the settings page
        return new RedirectResponse($this->generateUrl('admin_settings', ['tab' => 'maintenance']));
    }
}
