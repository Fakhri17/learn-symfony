<?php

namespace App\Controller\Admin;

use App\Entity\BlogCategory;
use App\Form\BlogCategoryType;
use App\Repository\BlogCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/category')]
#[IsGranted('ROLE_ADMIN')]
final class BlogCategoryController extends AbstractController
{
    #[Route('/', name: 'admin_category_index', methods: ['GET'])]
    public function index(BlogCategoryRepository $categoryRepository): Response
    {
        return $this->render('admin/blog_category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $category = new BlogCategory();
        $form = $this->createForm(BlogCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$category->getSlug()) {
                $category->setSlug($slugger->slug($category->getTitle())->lower());
            }
            
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'Category created successfully!');

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/blog_category/create.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BlogCategory $category, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(BlogCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$category->getSlug()) {
                $category->setSlug($slugger->slug($category->getTitle())->lower());
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Category updated successfully!');

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/blog_category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }
    #[Route('/{id}', name: 'admin_category_delete', methods: ['POST'])]
    public function delete(Request $request, BlogCategory $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
            $this->addFlash('success', 'Category deleted successfully!');
        }

        return $this->redirectToRoute('admin_category_index');
    }
}
