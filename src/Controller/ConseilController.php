<?php

namespace App\Controller;

use App\Repository\ConseilRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class ConseilController extends AbstractController
{
    #[Route('/api/conseils', name: 'app_conseil', methods: ['GET'])]
    public function getAllConseils(ConseilRepository $conseilRepository, SerializerInterface $serializer): JsonResponse
    {
        $currentMonth = (int) date('n');
        $conseils = $conseilRepository->findByMonth($currentMonth);
        $jsonConseils = $serializer->serialize($conseils, 'json');

        return new JsonResponse($jsonConseils, Response::HTTP_OK, [], true);
    }
}
