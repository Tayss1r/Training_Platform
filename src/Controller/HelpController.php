<?php

namespace App\Controller;

use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HelpController extends BaseController
{
    public function __construct(EntityManagerInterface $entityManager, SettingsService $settingsService)
    {
        parent::__construct($entityManager, $settingsService);
    }

    #[Route('/help', name: 'help_center')]
    public function index(): Response
    {
        return $this->render('help/index.html.twig');
    }
    
    #[Route('/help/contact', name: 'help_contact')]
    public function contact(): Response
    {
        // This could be expanded to handle contact form submissions
        return $this->render('help/contact.html.twig');
    }
}
