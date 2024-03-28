<?php

namespace App\Controller;

use App\HttpFoundation\EventStreamResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class DemoController extends AbstractController
{
    #[Route('/demo', name: 'app_demo')]
    public function index(): EventStreamResponse
    {
        return new EventStreamResponse(function () {
            $outcome = json_encode([
                'time' => date('H:i:s'),
            ]);

            sleep(1);

            return $outcome;
        });
    }
}
