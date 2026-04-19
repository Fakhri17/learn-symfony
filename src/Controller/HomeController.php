<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route("/", name: "app_home")]
    public function index(\App\Repository\BlogRepository $blogRepository): Response
    {
        return $this->render("index.html.twig", [
            "blogs" => $blogRepository->findBy(['isEnable' => true], ['createdAt' => 'DESC']),
        ]);
    }
}
