<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\UserRole;
use App\Service\PasswordHasher;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function __construct(
        private PasswordHasher $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('a@a.pl');
        $admin->setPassword($this->passwordHasher->hash('admin'));
        $admin->setFirstname('Admin');
        $admin->setLastname('User');
        $admin->setRole(UserRole::ADMINISTRATOR);
        $admin->setActive(true);

        $manager->persist($admin);
        $manager->flush();
    }
}
