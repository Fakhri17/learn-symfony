<?php

namespace App\Controller\Admin;

use App\Repository\BlogCategoryRepository;
use App\Repository\BlogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        BlogRepository $blogRepository,
        BlogCategoryRepository $categoryRepository,
        UserRepository $userRepository
    ): Response
    {
        return $this->render('admin/dashboard/index.html.twig', [
            'total_blogs' => $blogRepository->count([]),
            'total_categories' => $categoryRepository->count([]),
            'total_users' => $userRepository->count([]),
        ]);
    }
}
