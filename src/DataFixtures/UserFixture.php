<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('ahmed@gmail.com');
        $user->setFirstName('ahmed');
        $user->setLastName('melliti');
        $user->setRoles(['ROLE_USER']);
        $user->setImage('1.png');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'ahmed1'));
        $manager->persist($user);

        // Admin
        $admin = new User();
        $admin->setEmail('tayssir@gmail.com');
        $admin->setFirstName('tayssir');
        $admin->setLastName('ferhi');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setImage('5.png');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin1'));
        $manager->persist($admin);

        // Instructor
        $instructor = new User();
        $instructor->setEmail('malek@gmail.com');
        $instructor->setRoles(['ROLE_INSTRUCTOR']);
        $instructor->setFirstName('malek');
        $instructor->setLastName('khalifa');
        $instructor->setImage('7.png');
        $instructor->setPassword($this->passwordHasher->hashPassword($instructor, 'mohamed1'));
        $manager->persist($instructor);

        $manager->flush();
    }
}
