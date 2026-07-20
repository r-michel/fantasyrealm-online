<?php

namespace App\Controller;

use App\Entity\Equipment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EquipmentController extends AbstractController
{
    #[Route('/equipment', name: 'app_equipment')]
    public function index(): Response
    {
        return $this->render('equipment/index.html.twig', [
            'controller_name' => 'EquipmentController',
        ]);
    }

    #[Route(
        '/equipment/{id}/image',
        name: 'app_equipment_image',
        methods: ['GET']
    )]
    public function image(Equipment $equipment): Response
    {
        $image = $equipment->getImage();

        if (is_resource($image)) {
            $image = stream_get_contents($image);
        }

        if (!$image) {
            throw $this->createNotFoundException(
                'Cet équipement ne possède pas d’image.'
            );
        }

        return new Response($image, Response::HTTP_OK, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
