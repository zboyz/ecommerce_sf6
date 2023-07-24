<?php

namespace App\Controller;


use App\Repository\ImagesRepository;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/produits', name: 'products_')]
class ProductsController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('products/index.html.twig');
    }

    #[Route('/{id}-{slug}', name: 'details')]
    public function details($id, $slug, ImagesRepository $imagesRepository, ProductsRepository $productsRepository): Response
    {
        // Récupère les données du produit en utilisant le slug
        $products = $productsRepository->findOneBy(['slug' => $slug]);

        // Récupère les données du produit en utilisant le id
        $products = $productsRepository->findOneBy(['id' => $id]);

        // Récupère les données de l'image associée au produit
        $images = $imagesRepository->findOneBy(['products' => $products]);

        // Passe les données du produit et de l'image à la vue
        return $this->render('products/details.html.twig', [
            'product' => $products,
            'images' => $images,
        ]);
    }
}
