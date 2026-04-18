<?php

namespace App\DataFixtures;

use App\Entity\BlogCategory;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
  public function __construct(
    private UserPasswordHasherInterface $hasher
  ) {}

  public function load(ObjectManager $manager): void
  {
    // Admin user
    $admin = new User();
    $admin->setEmail('admin@email.test');
    $admin->setName('Administrator');
    $admin->setRoles(['ROLE_ADMIN']);
    $admin->setPassword(
      $this->hasher->hashPassword($admin, 'admin123')
    );
    $manager->persist($admin);

    // Blog categories
    $categories = [
      ['title' => 'Teknologi',   'slug' => 'teknologi'],
      ['title' => 'Tutorial',    'slug' => 'tutorial'],
      ['title' => 'Tips & Trik', 'slug' => 'tips-trik'],
      ['title' => 'Berita',      'slug' => 'berita'],
    ];

    foreach ($categories as $data) {
      $cat = new BlogCategory();
      $cat->setTitle($data['title']);
      $cat->setSlug($data['slug']);
      $manager->persist($cat);
    }

    $manager->flush();

    echo "Admin    → admin@email.test / admin123\n";
    echo "4 BlogCategory berhasil dibuat\n";
  }
}
