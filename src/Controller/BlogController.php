<?php

namespace App\Controller;

use App\Entity\Blog;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BlogController extends AbstractController
{
    #[Route('/blog/{slug}', name: 'app_blog_detail')]
    public function detail(
        #[MapEntity(mapping: ['slug' => 'slug'])] 
        Blog $blog
    ): Response
    {
        if (!$blog->isEnable()) {
            throw $this->createNotFoundException('Post not found or disabled.');
        }

        return $this->render('blog/detail.html.twig', [
            'blog' => $blog,
        ]);
    }
}
