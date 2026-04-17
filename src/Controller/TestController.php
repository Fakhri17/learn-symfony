<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TestController extends AbstractController
{
    #[Route("/test", name: "app_test")]
    public function index(): Response
    {
        $first_name = "Fakhri";
        $last_name = "Alauddin";

        return $this->render("test/index.html.twig", [
            "controller_name" => "TestController",
            "first_name" => $first_name,
            "last_name" => $last_name,
        ]);
    }
}
