<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class SettingsService
{
    private $settingsFile;
    private $settings = [];
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->settingsFile = $kernel->getProjectDir() . '/var/settings.json';
        $this->loadSettings();
    }

    /**
     * Load settings from file
     */
    private function loadSettings()
    {
        $filesystem = new Filesystem();

        if ($filesystem->exists($this->settingsFile)) {
            $content = file_get_contents($this->settingsFile);
            $this->settings = json_decode($content, true) ?: [];
        }
    }

    /**
     * Force reload settings from file
     */
    public function reloadSettings()
    {
        $this->loadSettings();
        return $this->settings;
    }

    /**
     * Save settings to file
     */
    private function saveSettings()
    {
        $filesystem = new Filesystem();
        $content = json_encode($this->settings, JSON_PRETTY_PRINT);

        // Ensure the directory exists
        $filesystem->mkdir(dirname($this->settingsFile));

        // Write the settings to the file
        file_put_contents($this->settingsFile, $content);
    }

    /**
     * Get a setting value by name
     */
    public function get(string $name, $default = null)
    {
        foreach ($this->settings as $category => $categorySettings) {
            if (isset($categorySettings[$name])) {
                return $categorySettings[$name];
            }
        }

        return $default;
    }

    /**
     * Save a setting
     */
    public function set(string $name, string $value, string $category)
    {
        if (!isset($this->settings[$category])) {
            $this->settings[$category] = [];
        }

        $this->settings[$category][$name] = $value;
        $this->saveSettings();

        return true;
    }

    /**
     * Get all settings by category
     */
    public function getByCategory(string $category): array
    {
        return $this->settings[$category] ?? [];
    }

    /**
     * Save multiple settings at once
     */
    public function saveMultiple(array $settings, string $category)
    {
        if (!isset($this->settings[$category])) {
            $this->settings[$category] = [];
        }

        foreach ($settings as $name => $value) {
            $this->settings[$category][$name] = $value;
        }

        $this->saveSettings();
    }

    /**
     * Initialize default settings if they don't exist
     */
    public function initializeDefaults()
    {
        // General settings
        $this->initCategory('general', [
            'site_name' => 'Forminy',
            'site_description' => 'A comprehensive learning management system for educational institutions.',
            'admin_email' => 'admin@forminy.com',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d'
        ]);

        // Email settings
        $this->initCategory('email', [
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_username' => 'user@example.com',
            'smtp_password' => 'password',
            'smtp_encryption' => 'tls',
            'from_email' => 'noreply@forminy.com',
            'from_name' => 'Forminy LMS'
        ]);

        // Security settings
        $this->initCategory('security', [
            'password_min_length' => '1',
            'password_require_uppercase' => '1',
            'password_require_numbers' => '1',
            'password_require_special' => '0',
            'session_timeout' => '30',
            'login_attempts' => '5',
            'two_factor_auth' => '0'
        ]);

        // Appearance settings
        $this->initCategory('appearance', [
            'theme' => 'default',
            'primary_color' => '#696cff',
            'logo' => '',
            'favicon' => ''
        ]);

        // Maintenance settings
        $this->initCategory('maintenance', [
            'maintenance_mode' => '0',
            'maintenance_message' => 'We are currently performing scheduled maintenance. Please check back soon.'
        ]);

        // Save all settings
        $this->saveSettings();
    }

    /**
     * Initialize a category with default settings if it doesn't exist
     */
    private function initCategory(string $category, array $defaults)
    {
        if (!isset($this->settings[$category])) {
            $this->settings[$category] = $defaults;
        } else {
            // Add any missing settings
            foreach ($defaults as $key => $value) {
                if (!isset($this->settings[$category][$key])) {
                    $this->settings[$category][$key] = $value;
                }
            }
        }
    }
}
