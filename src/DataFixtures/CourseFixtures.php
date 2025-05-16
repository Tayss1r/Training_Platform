<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $categories = $manager->getRepository(Category::class)->findAll();

        if (empty($categories)) {
            throw new \Exception('No categories found. Please load CategoryFixtures first.');
        }

        $courses = [
            [
                'title' => 'Introduction to Programming',
                'description' => 'Learn the basics of programming with this introductory course. Perfect for beginners with no prior experience.',
                'duration' => 20,
            ],
            [
                'title' => 'Advanced Web Development',
                'description' => 'Take your web development skills to the next level with advanced techniques and frameworks.',
                'duration' => 30,
            ],
            [
                'title' => 'Data Science Fundamentals',
                'description' => 'Explore the world of data science and learn how to analyze and visualize data effectively.',
                'duration' => 25,
            ],
            [
                'title' => 'Mobile App Development',
                'description' => 'Build mobile applications for iOS and Android platforms using modern frameworks and tools.',
                'duration' => 35,
            ],
            [
                'title' => 'Cybersecurity Essentials',
                'description' => 'Learn essential cybersecurity concepts and practices to protect systems and data from threats.',
                'duration' => 15,
            ],
            [
                'title' => 'Cloud Computing',
                'description' => 'Understand cloud computing concepts and learn to deploy applications on major cloud platforms.',
                'duration' => 20,
            ],
            [
                'title' => 'Artificial Intelligence Basics',
                'description' => 'Introduction to AI concepts, machine learning algorithms, and practical applications.',
                'duration' => 25,
            ],
            [
                'title' => 'DevOps Practices',
                'description' => 'Learn DevOps methodologies and tools to improve software development and deployment processes.',
                'duration' => 18,
            ],
            [
                'title' => 'Blockchain Technology',
                'description' => 'Understand blockchain fundamentals and explore its applications beyond cryptocurrencies.',
                'duration' => 22,
            ],
            [
                'title' => 'UI/UX Design Principles',
                'description' => 'Learn user interface and user experience design principles to create effective digital products.',
                'duration' => 15,
            ],
        ];

        foreach ($courses as $courseData) {
            $course = new Course();
            $course->setTitle($courseData['title']);
            $course->setDescription($courseData['description']);
            $course->setDuration($courseData['duration']);

            // Assign a random category
            $randomCategory = $categories[array_rand($categories)];
            $course->setCategory($randomCategory);

            // Set created at
            $course->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($course);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }
}
