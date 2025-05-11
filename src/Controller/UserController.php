<?php

namespace App\Controller;

use App\Form\AccountDeleteType;
use App\Form\PasswordChangeType;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class UserController extends AbstractController
{
    #[Route('/user/profile', name: 'app_user_profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Assuming the user is authenticated
        $user = $this->getUser();

        // Create the form to update user profile
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $photoFile = $form->get('image')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $directory = $this->getParameter('imageDirectory');
                    $photoFile->move($directory, $newFilename);
                    $user->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error while uploading image: ' . $e->getMessage());
                }
            }

            // Save changes to the database
            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully.');

            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/profile.html.twig', [
            'controller_name' => 'UserController',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/settings', name: 'app_user_settings')]
    public function settings(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage
    ): Response
    {
        // Assuming the user is authenticated
        $user = $this->getUser();

        // Form for password change
        $passwordForm = $this->createForm(PasswordChangeType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $newPassword = $passwordForm->get('newPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $entityManager->flush();
            $this->addFlash('success', 'Password changed successfully.');

            return $this->redirectToRoute('app_user_settings');
        }

        // Form for account deletion
        $deleteForm = $this->createForm(AccountDeleteType::class);
        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            // Remove the user from the database
            $entityManager->remove($user);
            $entityManager->flush();

            // Clear the security token (log out the user)
            $tokenStorage->setToken(null);
            
            // Clear the session
            $request->getSession()->invalidate();
            
            $this->addFlash('success', 'Account deleted successfully.');

            return $this->redirectToRoute('home');
        }

        return $this->render('user/settings.html.twig', [
            'controller_name' => 'UserController',
            'passwordForm' => $passwordForm->createView(),
            'deleteForm' => $deleteForm->createView(),
        ]);
    }

}
