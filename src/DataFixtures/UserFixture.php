<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('ahmed@gmail.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'ahmed1'));
        $manager->persist($user);

        // Admin
        $admin = new User();
        $admin->setEmail('tayssir@gmail.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin1'));
        $manager->persist($admin);

        // Instructor
        $instructor = new User();
        $instructor->setEmail('mohamed@gmail.com');
        $instructor->setRoles(['ROLE_INSTRUCTOR']);
        $instructor->setPassword($this->passwordHasher->hashPassword($instructor, 'mohamed1'));
        $manager->persist($instructor);

        $manager->flush();
    }
}
