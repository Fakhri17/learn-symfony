<?php

namespace App\Controller\Admin;

use App\Entity\Blog;
use App\Form\BlogType;
use App\Repository\BlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/blog')]
#[IsGranted('ROLE_ADMIN')]
final class BlogController extends AbstractController
{
    #[Route('/', name: 'admin_blog_index', methods: ['GET'])]
    public function index(BlogRepository $blogRepository): Response
    {
        return $this->render('admin/blog/index.html.twig', [
            'blogs' => $blogRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_blog_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $blog = new Blog();
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$blog->getSlug()) {
                $blog->setSlug($slugger->slug($blog->getTitle())->lower());
            }
            
            $thumbnailFile = $form->get('thumbnail')->getData();
            if ($thumbnailFile) {
                $newFilename = $this->uploadThumbnail($thumbnailFile, $slugger);
                $blog->setThumbnail($newFilename);
            }

            $blog->setAuthor($this->getUser());
            
            $entityManager->persist($blog);
            $entityManager->flush();

            $this->addFlash('success', 'Blog post created successfully!');

            return $this->redirectToRoute('admin_blog_index');
        }

        return $this->render('admin/blog/create.html.twig', [
            'blog' => $blog,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_blog_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Blog $blog, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$blog->getSlug()) {
                $blog->setSlug($slugger->slug($blog->getTitle())->lower());
            }
            
            $deleteThumbnail = $form->get('delete_thumbnail')->getData();
            $thumbnailFile = $form->get('thumbnail')->getData();

            if ($deleteThumbnail) {
                // Optionally delete physical file from disk here
                $blog->setThumbnail(null);
            }

            if ($thumbnailFile) {
                $newFilename = $this->uploadThumbnail($thumbnailFile, $slugger);
                $blog->setThumbnail($newFilename);
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Blog post updated successfully!');

            return $this->redirectToRoute('admin_blog_index');
        }

        return $this->render('admin/blog/edit.html.twig', [
            'blog' => $blog,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_blog_delete', methods: ['POST'])]
    public function delete(Request $request, Blog $blog, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$blog->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($blog);
            $entityManager->flush();
            $this->addFlash('success', 'Blog post deleted successfully!');
        }

        return $this->redirectToRoute('admin_blog_index');
    }
    private function uploadThumbnail($file, SluggerInterface $slugger): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move(
                $this->getParameter('thumbnails_directory'),
                $newFilename
            );
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
            throw new \Exception('Failed to upload thumbnail');
        }

        return $newFilename;
    }
}
